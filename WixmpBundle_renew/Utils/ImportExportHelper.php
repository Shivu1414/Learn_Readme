<?php

namespace Webkul\Modules\Wix\WixmpBundle\Utils;

class ImportExportHelper extends WixMpBaseHelper
{
    public function fn_export($pattern, $export_fields, $options)
    {
        if (empty($pattern) || empty($export_fields)) {
            return false;
        }
        
        $enclosure = (isset($pattern['enclosure'])) ? $pattern['enclosure'] : '"';

        return $this->convert_to_csv($export_fields, $options, $enclosure);
    }

    // Put data to cache and expire after 1 day
    // Parameters:
    // @data - export data
    // @options - options
    public function convert_to_csv(&$data, &$options, $enclosure)
    {
        static $output_started = false;
        $eol = "\n";
        if ($options['delimiter'] == 'C') {
            $delimiter = ',';
        } elseif ($options['delimiter'] == 'T') {
            $delimiter = "\t";
        } else {
            $delimiter = ';';
        }
        $cacheName = $this->companyApplication->getCompany()->getId().'_'.$options['filename'];
        $hasInCache = $this->cache->hasItem($cacheName);
        $file_csv_data_cache = $this->cache->getItem($cacheName);
        $file_csv_data = $file_csv_data_cache->get();
        $csvHeader = [];
        if (!$hasInCache) { // first line add header
            $csvHeader = array_keys($data[0]);
        }
        foreach ($data as $k => &$v) {
            foreach ($v as $name => &$value) {
                $data[$k][$name] = $enclosure.str_replace(array("\r", "\n", "\t", $enclosure), array('', '', '', $enclosure.$enclosure), $value).$enclosure;
            }
            if (substr($data[$k][$name], -3) == '"""') {
                $data[$k][$name] .= ' ';
            }
        }
        
        $csv = $this->renderView('@wixmp_twig/view_templates/ImportExport/export.csv.twig', [
            'export_data' => $data,
            'delimiter' => $delimiter,
            'eol' => $eol,
            'fields' => $csvHeader,
        ]);

        if (!empty($file_csv_data)) {
            $csv = $file_csv_data.$csv; // append new values
        }

        $file_csv_data_cache->set($csv);
        $file_csv_data_cache->expiresAfter(86400);  // data saved only for 1 day Increase if necessary
        $this->cache->save($file_csv_data_cache);
        
        return $csv;
    }
}
