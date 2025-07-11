<?php


namespace App\Classes\GoAML;

use App\Models\User;
use App\Repositories\ActionRepository;
use App\Repositories\UserRepository;
use SimpleXMLElement;
use DOMDocument;
use ZipArchive;

class Export
{

    const AML_ZIP_FILE_NAME = "reports.zip";

    /**
     * Write the action log to the database actions table
     *
     * @param User $user user reporting on
     * @param string $jurisdiction Jurisdiction reporting on
     * @param integer $type type of log
     *
     * @return void
     *
     * @throws Exception
     * @throws \Exception
     */
    public function doLogAction($user, $jurisdiction, $type)
    {
        if (isset($user)) {
            $type_map = [
                'success_single' => [
                    'go_aml_export_single_xml',
                    "generated and exported an AML report for user with id 
                    {$user->getKey()} in jurisdiction {$jurisdiction->getSelectedJurisdiction()}"
                ],
                'success_zip' => [
                    'go_aml_export_zip',
                    "generated and exported a ZIP file
                     of multiple AML reports for user with id 
                    {$user->getKey()} in jurisdiction {$jurisdiction->getSelectedJurisdiction()}"
                ],
                'no_results' => [
                    'go_aml_export_attempt_no_results',
                    "attempted to generated a AML report for user with id 
                    {$user->getKey()} in jurisdiction {$jurisdiction->getSelectedJurisdiction()} 
                    but no transactions were found"
                ],
            ];
        } else {
            $type_map = [
                'no_user'  => [
                    'go_aml_export_attempt_no_user_id',
                    'there was an attempt to export without a user id'
                ]
            ];
        }
        
        ActionRepository::logAction(
            UserRepository::getCurrentUser(),
            $type_map[$type][1],
            $type_map[$type][0]
        );
    }
    
    /**
     * Export the given XML file
     *
     * @param SimpleXMLElement $xml
     * @param User $user
     * @param $jurisdiction
     *
     * @return string
     * @throws \Exception
     */
    public function exportGoAML(SimpleXMLElement $xml, $user, $jurisdiction)
    {
        $name = uniqid(rand(), true) . '.xml';

        header('Content-Disposition: attachment;filename=' . $name);
        header('Content-Type: text/xml');
        ob_clean();
        $this->doLogAction($user, $jurisdiction,  'success_single');
        return $this->formatXml($xml);
    }
    
    /**
     * Export the zip file with all the xml files for the report
     *
     * @param $user_id
     * @param $jurisdiction
     *
     * @throws \Exception
     */
    public function exportZipTransactions($user_id, $jurisdiction)
    {
        ignore_user_abort(true);
        $zip_file_name = self::AML_ZIP_FILE_NAME;

        $file = $this->getStorageFolder() . $zip_file_name;
   
        if (file_exists($file)) {
            header('Content-Disposition: attachment;filename=' . $zip_file_name);
            header('Content-Type: text/xml');

            if (readfile($file)) {
                ob_end_flush();
                unlink($file);
            }
            $this->doLogAction($user_id, $jurisdiction, 'success_zip');
        }
    }

    /**
     * Return the storage report path
     *
     * @return string
     */
    public function getStorageFolder()
    {
        return getenv('STORAGE_PATH') . "/";
    }

    /**
     * Format the xml to look better and easier to read
     *
     * @param SimpleXMLElement $simpleXMLElement
     * @return string
     */
    public function formatXml(SimpleXMLElement $simpleXMLElement)
    {
        $xmlDocument = new DOMDocument('1.0');
        $xmlDocument->preserveWhiteSpace = false;
        $xmlDocument->formatOutput = true;
        $xmlDocument->loadXML($simpleXMLElement->asXML());
        return $xmlDocument->saveXML();
    }

    /**
     * Add the xml files inside a zip file
     *
     * @param $xml
     */
    public function prepareZipFile($xml)
    {
        $name = uniqid(rand(), true) . '.xml';

        $zip_file_name = self::AML_ZIP_FILE_NAME;

        $new_xml_file = $this->formatXml($xml);

        $zip = new ZipArchive;

        $res = $zip->open($this->getStorageFolder() . $zip_file_name, ZipArchive::CREATE);
        if ($res === true) {
            $zip->addFromString($name, $new_xml_file);
            $zip->close();
        }
    }
}
