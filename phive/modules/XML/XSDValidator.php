<?php
declare(strict_types=1);

namespace XML;

use DOMDocument;
use Exception;
use PhpZip\Exception\ZipException;
use PhpZip\ZipFile;

class XSDValidator
{

    protected string $xsd_dir;

    /**
     * @param string $xsd_dir The path to a directory with xsd files
     */
    public function __construct(string $xsd_dir)
    {
        $this->xsd_dir = $xsd_dir;
    }

    /**
     * @param string $filename file to open, unless $content is set. In that case just reported name
     * @param string|null $content
     * @return string[] list of errors
     */
    public function validateXML(string $filename, ?string $content = null): array
    {
        $prev_libxml = libxml_use_internal_errors(true);

        $errors = [];

        $doc = new DOMDocument();

        if ($content) {
            $doc->loadXML($content);
        } else {
            $doc->load($filename);
        }

        $schema_location = $doc->firstChild->attributes->getNamedItem('schemaLocation')->nodeValue;
        $schemas = explode(' ', $schema_location);

        $schema_files = [];

        foreach ($schemas as $temp_schema) {
            $temp_schema = basename($temp_schema);
            $temp_schema = "{$this->xsd_dir}/{$temp_schema}";
            if (file_exists($temp_schema)) {
                $schema_files[] = $temp_schema;
            }
        }

        if (!$schema_files) {
            $errors[] = "Schema not found for validation: {$schema_location}";
        }

        // Create a temporary schema that integrates every valid schema found
        $schema = '<?xml version="1.0" encoding="UTF-8"?><schema xmlns="http://www.w3.org/2001/XMLSchema" version="0.1" elementFormDefault="qualified" targetNamespace="xsdtest">';
        foreach ($schema_files as $schema_file) {
            $schema .= '<import schemaLocation="' . $schema_file . '"/>';
        }
        $schema .= '</schema>';

        if (!$errors && !$doc->schemaValidateSource($schema)) {
            foreach (libxml_get_errors() as $error) {
                $errors[] = trim($error->message) . '(Line: ' . $error->line . ")";
            }
            libxml_clear_errors();
        }

        libxml_use_internal_errors($prev_libxml);

        return $errors;

    }

    /**
     * Open a zip file and pass every xml inside to @see validateXml
     *
     * @param string $zip_file
     * @param string|null $password
     * @return array[] list of errors per file
     */
    public function processZip(string $zip_file, string $password = null): array
    {
        $errors = [];

        $zip = new ZipFile();

        try {
            $zip->openFile($zip_file);
        } catch (ZipException $e) {
            $errors[$zip_file] = [$e->getMessage()];
            return $errors;
        }

        if (!is_null($password)) {
            $zip->setReadPassword($password);
        }

        foreach ($zip->getEntries() as $entry) {
            $filename = $entry->getName();

            if (pathinfo($filename, PATHINFO_EXTENSION) === 'xml') {

                try {
                    $data = $entry->getData()->getDataAsString();
                } catch (Exception $e) {
                    if ($entry->isEncrypted()) {
                        if (empty($password)) {
                            $errors[$zip_file] = ['File is encrypted, but no password was provided.'];
                        } else {
                            $errors[$zip_file] = ['File is encrypted, invalid password was provided.'];
                        }
                    } else {
                        $errors[$zip_file] = ['Unidentified error, can\'t access file contents. ' . $e->getMessage()];
                    }
                    return $errors;
                }

                $res = $this->validateXML($filename, $data);

                if ($res) {
                    $errors[$filename] = $res;
                }
            }

        }

        return $errors;
    }

    /**
     * Main entry point, pass a XML or ZIP file and it will return an array of [filename => errors]
     * Selection is done simply by file extension
     *
     * @param string $filename
     * @param string|null $password optional password for zip files
     * @return array
     */
    public function validate(string $filename, ?string $password = null): array
    {
        $errors = [];

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        if ($extension === 'zip') {
            $errors = $this->processZip($filename, $password);
        } elseif ($extension === 'xml') {
            $res = $this->validateXML($filename);
            if ($res) {
                $errors[$filename] = $res;
            }
        } else {
            $errors[$filename] = ['File type not recognized'];
        }

        return $errors;
    }
}