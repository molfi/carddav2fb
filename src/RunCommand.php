<?php

namespace Andig;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class RunCommand extends Command
{
    use ConfigTrait;

    protected function configure()
    {
        $this->setName('run')
            ->setDescription('Download, convert and upload - all in one')
            ->addOption('image', 'i', InputOption::VALUE_NONE, 'download images');

        $this->addConfig();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->loadConfig($input);

        // we want to check for image upload show stoppers as early as possible
        if ($input->getOption('image')) {
            $precresult = $this->uploadImagePreconditionsOK($this->config['fritzbox'], $this->config['phonebook']);
            if ($precresult !== true) {
                error_log($precresult.PHP_EOL);
                return(21);                     // error code to evaluate by shell
            }
        }

        // compare timestamp of CardDAV against last update on Fritz!Box
        $lastupdate = 0;
        $latestmod  = 0;
        $recentPhonebook = downloadPhonebook ($this->config);           // is needed for forecedupload > 1 as well

        if ($this->config['phonebook']['forcedupload'] < 2) {

            error_log("Determine the last change of the FRITZ!Box phonebook");
            // date_default_timezone_set('CET');
            $lastupdate = $recentPhonebook->phonebook->timestamp;       // get timestamp from phonebook
            error_log("Determine the last change(s) on the CardDAV server(s)");            
            foreach ($this->config['server'] as $server) {              // determine the youngest modification date
                $backend = backendProvider($server);
                $timeCache = getlastmodification ($backend);
                if ($timeCache > $latestmod) {
                    $latestmod = $timeCache;
                }
            }
        }
        if ($lastupdate > $latestmod) {
            error_log("Your Fritz!Box phonebook is more recent than the contacts on the CardDAV server");
        }
        else {
            $vcards = array();
            $xcards = array();
            $substitutes = ($input->getOption('image')) ? ['PHOTO'] : [];

            foreach ($this->config['server'] as $server) {
                $progress = new ProgressBar($output);
                error_log("Downloading vCard(s) from account ".$server['user']);

                $backend = backendProvider($server);
                $progress->start();
                $xcards = download($backend, $substitutes, function () use ($progress) {
                    $progress->advance();
                });
                $progress->finish();
                $vcards = array_merge($vcards, $xcards);
                $quantity = count($vcards);
                error_log(sprintf(PHP_EOL."Downloaded %d vCard(s)", $quantity));
            }

            // dissolve
            error_log("Dissolving groups (e.g. iCloud)");
            $cards = dissolveGroups($vcards);
            $remain = count($cards);
            error_log(sprintf("Dissolved %d group(s)", $quantity - $remain));

            // filter
            error_log(sprintf("Filtering %d vCard(s)", $remain));
            $filters = $this->config['filters'];
            $filtered = filter($cards, $filters);
            error_log(sprintf("Filtered out %d vCard(s)", $remain - count($filtered)));

            // image upload
            if ($input->getOption('image')) {
                error_log("Detaching and uploading image(s)");
                $imgProgress = new ProgressBar($output);
                $imgProgress->start(count($filtered));
                $pictures = uploadImages($filtered, $this->config['fritzbox'], $this->config['phonebook'], function () use ($imgProgress) {
                        $imgProgress->advance();
                });
                if ($pictures) {
                    error_log(sprintf("Uploaded/refreshed %d of %d image file(s)", $pictures[0], $pictures[1]));
                }
                $imgProgress->finish();
            } else {
                unset($this->config['phonebook']['imagepath']);             // otherwise convert will set wrong links
            }

            // fritzbox format
            $xml = export($filtered, $this->config);
            error_log(sprintf(PHP_EOL."Converted %d vCard(s)", count($filtered)));

            // check for newer contacts in phonebook
            if ($this->config['phonebook']['forcedupload'] < 3) {
                error_log("Checking FRITZ!Box for newer entries");
                $i = checkUpdates($recentPhonebook, $xml, $this->config);
                if ($i) {
                    error_log(sprintf("Saved %d newer entries from FRITZ!Box phonebook", $i));
                }
            }

            // fax number upload
            if (isset($this->config['fritzbox']['fritzadr'])) {
                error_log("Selecting and uploading fax number(s) for FRITZ!fax");
                $i = uploadFritzAdr($xml, $this->config);
                if ($i) {
                    error_log(sprintf("Uploaded %d fax number entries into fritzadr.dbf", $i));
                }
            }

            // upload
            error_log("Uploading");
            $xmlStr = $xml->asXML();
            upload($xmlStr, $this->config);
            error_log("Successful uploaded new Fritz!Box phonebook");
        }
    }


    /**
     * checks if preconditions for upload images are OK
     *
     * @return            mixed     (true if all preconditions OK, error string otherwise)
     */
    private function uploadImagePreconditionsOK($configFritz, $configPhonebook)
    {
        if (!function_exists("ftp_connect")) {
            return "ERROR: FTP functions not available in your PHP installation.".PHP_EOL.
                    "       Image upload not possible (remove -i switch)".PHP_EOL.
                    "       Ensure PHP was installed with --enable-ftp".PHP_EOL.
                    "       Ensure php.ini does not list ftp_* functions in 'disable_functions'".PHP_EOL.
                    "       In shell run: php -r \"phpinfo();\" | grep FTP";
        }
        if (!$configFritz['fonpix']) {
            return "ERROR: config.php missing fritzbox/fonpix setting.".PHP_EOL.
                    "       Image upload not possible (remove -i switch).";
        }
        if (!$configPhonebook['imagepath']) {
            return "ERROR: config.php missing phonebook/imagepath setting.".PHP_EOL.
                    "       Image upload not possible (remove -i switch).";
        }
        return true;
    }
}