<?php

echo "Converting PO files to MO files...\n";

$po_files = glob("*.po");

foreach ($po_files as $po_file) {
    $base_name = basename($po_file, '.po');
    
    $mo_file = $base_name . '.mo';
    
    $command = "msgfmt -o $mo_file $po_file";
    exec($command, $output, $return_var);
    
    if ($return_var === 0) {
        echo "Successfully created: $mo_file\n";
    } else {
        echo "Error creating: $mo_file\n";
        echo "Error output: " . implode("\n", $output) . "\n";
    }
}

echo "Conversion complete!\n"; 