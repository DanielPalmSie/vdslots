<?php
declare(strict_types=1);

namespace ES\ICS\Validation\Traits;


use ES\ICS\Constants\ICSConstants;
use PhpZip\Constants\ZipEncryptionMethod;
use PhpZip\Exception\ZipException;
use PhpZip\Model\ZipEntry;
use PhpZip\ZipFile;

trait JUCheckFiles
{
    /**
     * @var string Type to ignore in the extra files check
     */
    protected string $ignore_type;

    /**
     * Check the zip files for correct structure
     * Redefined from checkFiles because JU works a little differently
     * @param array $reports
     * @param int $date
     * @return array
     */
    protected function checkFiles(array $reports, int $date): array
    {
        $operator_id = $this->license->getLicSetting('operatorId');
        $storage_id = $this->license->getLicSetting('storageId');
        $base_path = $this->license->getLicSetting('ICS')['export_folder'] . '/' . $operator_id;

        $formatted_date = date(ICSConstants::DAY_FORMAT, $date);
        $remaining_files = (bool)glob($base_path . '/JU/' . $formatted_date . '/*/*/*.zip');

        $zip_parent = new ZipFile();
        $zip = new ZipFile();
        $pass = $this->license->getLicSetting('regulation_password');

        $parent_error = false;
        $files_in_zip = [];

        $daily_filename = $base_path . '/JU/Anteriores/' . implode('_', [
                $operator_id,
                $storage_id,
                'JU',
                'DIARIO',
                $formatted_date
            ]) . '.zip';


        $daily_exists = file_exists($daily_filename);

        if ($daily_exists) {
            try {
                $zip_parent->openFile($daily_filename);

                //get all the files in the zip, so we can know if we have a file we shouldn't have
                $files_in_zip = array_reduce(
                    $zip_parent->getEntries(),
                    function (array $carry, ZipEntry $entry) {
                        if (!$entry->isDirectory()) {
                            $filename = $entry->getName();
                            //We ignore the other type of JU files
                            if (explode('/', $filename)[1] !== $this->ignore_type) {
                                $carry[$filename] = 1;
                            }
                        }
                        return $carry;
                    },
                    []);


            } catch (ZipException $e) {
                $parent_error = 'Error reading container: ' . $e->getMessage();
                $this->checks['wrong zip format'] = true;
            }
        }
        foreach ($reports as &$report) {
            $report['found_files_on_disk'] = $remaining_files;

            $report['zip'] = 'Skipped';
            $report['file_exists'] = 'Skipped';
            $report['xsd'] = 'Skipped';
            $report['dates'] = 'Skipped';
            $report['same_data_in_db'] = 'Skipped';
            foreach ($this->additional_fields as $field) {
                $report[$field] = 'Skipped';
            }

            $report['daily_filename'] = $daily_filename;
            $report['daily_file_exists'] = $daily_exists;

            //we only have in the zip the last 3 parts: date/[JUD|JUT]/game_type
            $zip_path = implode('/', array_slice(explode('/', $report['file_path']), -3));
            $filename = $zip_path . '/' . $report['filename_prefix'] . '.zip';
            $report['filename'] = $filename;

            if ($parent_error) {
                $report['zip'] = $parent_error;
                continue;
            }

            try {
                $file = $zip_parent->getEntryStream($filename);
                unset($files_in_zip[$filename]);
                $report['file_exists'] = true;
            } catch (ZipException $e) {
                $report['file_exists'] = false;
                $this->checks['files missing'] = true;

                $report['zip'] = 'File not found inside container: ' . $filename;
                $this->checks['wrong zip format'] = true;
                continue;
            }
            try {
                $zip->openFromStream($file);

                if ($zip->count() !== 1 || !$zip->hasEntry('enveloped.xml')) {
                    $report['zip'] = 'Required filename not found.';
                    $this->checks['wrong zip structure'] = true;
                    continue;
                }
                if (($em = $zip->getEntry('enveloped.xml')->getEncryptionMethod()) !== ZipEncryptionMethod::WINZIP_AES_256) {
                    $report['zip'] = 'Invalid encryption used: ' . ZipEncryptionMethod::getEncryptionMethodName($em);
                    $this->checks['wrong zip format'] = true;
                    continue;
                }
            } catch (ZipException $e) {
                $report['zip'] = 'Error reading file: ' . $e->getMessage();
                $this->checks['wrong zip format'] = true;
                continue;
            }
            $report['zip'] = 'Valid';
            $zip->setReadPassword($pass);
            try {
                if ($validation_errors = $this->xsd_validator->validateXML($filename,
                    $zip->getEntryContents('enveloped.xml'))) {
                    $report['xsd'] = [$validation_errors];
                    continue;
                }
            } catch (ZipException $e) {
                $report['zip'] = 'Error reading file: ' . $e->getMessage();
                $this->checks['wrong zip format'] = true;
                continue;
            }

            $report['xsd'] = 'Valid';

        }
        unset($report);

        foreach ($reports as &$report) {
            $report['daily_with_extra_files'] = !empty($files_in_zip);
        }

        return $reports;

    }
}
