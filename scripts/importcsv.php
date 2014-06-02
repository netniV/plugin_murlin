#!/usr/bin/php

<?php

# Process a CSV file
# COMMAND LINE ONLY
if (php_sapi_name() != 'cli')
{
    print "This can only be run from the command line. Exiting.";
    die();
}

/* We are not talking to the browser */
$no_http_headers = true;

# include some cacti files for ease of use
include_once(dirname(__FILE__) . "/../../../include/global.php");

// Command line args
$import = false;
$verbose = false;
$validate = false;
$lines = null;
$force = null;

for($i=0; $i<count($argv); $i++)
{
    if ($argv[$i] == "--validate" || $argv[$i] == "-v")
        $validate = true;
    
    if ($argv[$i] == "--force" || $argv[$i] == "-f" || $argv[$i] == "--ignore-duplicates")
        $force = true;
    
    if ($argv[$i] == "--verbose")
        $verbose = true;
    
    if ($argv[$i] == "--import" || $argv[$i] == "-i")
        $import = true;
    
    if ($argv[$i] == "--lines" || $argv[$i] == "-l")
    {
        $lines = explode(",", $argv[$i+1]);
        
        foreach ($lines as $l)
        {
            if (!is_numeric($l))
            {
                print "Error - Specified line $l is not a number.\n";
                die();
            }
        }
    }
        
}

if (!$import && !$validate)
{
    print "Usage:
        php importcsv.php <filepath> [--validate] [--import] 
                                    [--verbose] [--lines <linenumbers>]\n
        Example:
            php importcsv.php file.csv --validate -l 1,2,32
            
        Arguments:
            --validate          Validate the CSV, if used in conjunction with
             -v                 the --import flag will prompt to continue.
            
            --import            Import the CSV into the database. If used in 
             -i                 conjuntion with the --validate flag it will
                                prompt before importing any data.
                                
                                NOTE: Regardless of if the --validate flag isn't
                                used, the script will never import data it 
                                considers to be in error, ie if it fails to 
                                validate a line, that line will be skipped when
                                the import runs.
            
            --verbose           Print to screen the contents of the CSV as
                                interpreted by the script. Must be used in 
                                conjunction with one of --import or --validate.
                                
            --lines             Process the line numbers specified. Comma 
             -l                 separated.
             
            --force             Will force the import of a URL even if the URL
             -f                 already exists within the mURLin database.
             
            --ignore-duplicates Same as force, site will be imported even if a
                                duplicate exists in the DB.
          
";
    die();
}


if (VerifyCSV($argv[1], $numberOfErrors, $totalLines, $records, $verbose, $lines))
{
    // If we are validating only die early
    if ($validate && !$import)
        die();
    
    if ($numberOfErrors > 0)
    {
        print "There were $numberOfErrors line errors detected in the CSV file. Continue [y/N]?\n";
        $answer = fgets(STDIN);
        
        while ($answer != "y\n" || $answer != "n\n")
        {
            if (strtolower($answer) == "n\n" || $answer == "\n")
            {
                print "\nNo data has been imported. Exiting...\n";
                die();
            }
            elseif (strtolower($answer) == "y\n")
            {
                break;
            }
             
            print "Enter Y or N. Continue [y/N]?\n";
            $answer = fgets(STDIN);
        }
    }


    print "\nImporting CSV Data..\n";
    ImportCSV($argv[1], $verbose, $lines, $force);
}
else
{
    print "There was an error opening the file.\n";
}

function ImportCSV($filepath, $verbose = false, $lines = null, $force = false)
{
    $processedLines = 0; // How many lines have we ACTUALLY dealt with
    
    $lineNumber = 0;
    $lineCount = 0;
    $lineErrors = 0;
    $duplicates = false;
    
    $handle = fopen("$filepath", "r");
    if ($handle) 
    {
        # End of file flag
        $eof = false;

        print "\n\nOpening file " . $filepath . "\n";
        print "Importing the CSV...\n";

        while (!$eof) 
        {
            $line = fgetcsv($handle);

            # Flag to denote error on a particular line            
            $err = false;

            if ($line == false)
            {
                $eof = true;
                break;
            }
            
            if (!isset($line[1]) && empty($line[0]))
            {
                # Empty line, move to next line
                
                ## Increment the counters so that we get a 'good' line
                ## but one which is completely ignored in the stats.
                $lineNumber++;
                $lineCount++;
                
                continue;
            }

            # We have a line to verify        
            $lineNumber++;
            
            # Check if lineNumber is in the requested lines (if applicable)
            if (!is_null($lines))
            {
                # We have some lines!
                if (!in_array($lineNumber, $lines))
                {
                    # We aren't interested in this line
                    
                    # To ensure this line isn't counted in the stats ensure its
                    # counted as a good (but ignored) line.
                    $lineCount++;
                    continue;
                }
            }
            
            if ($verbose)
            {
                print "\nLine: $lineNumber\n";
                print_r($line);
            }

            if (ValidateLine($line, $lineNumber, $force))
            {
                # We have verified the line
                $lineCount++;
                
                # Line verified check for duplicate
                $urld = mysql_real_escape_string($line[1]);
                $sql = "SELECT url FROM plugin_mURLin_index WHERE url='" . $urld . "';";
                
                $result = db_fetch_assoc($sql);
                
                if (count($result) > 0)
                {
                    # DUPLICATES
                    print "Warning - Line $lineNumber: The URL " . $urld . " already exists in the DB.\n";
                    $duplicates = true;
                }
                
                if ($verbose && (count($result) == 0 || $force))
                    print "Importing line $lineNumber ";
                
                // Import into the DB
                # HOST,URL,TEXTMATCH,TIMEOUT,ENABLEPROXY,PROXYHOST,PROXYUSERNAME,PROXYPASSWORD
                $host = is_numeric($line[0]) ? $line[0] : 0;
                $url = mysql_real_escape_string($line[1]);
                $text_match = mysql_real_escape_string($line[2]);
                $timeout = is_numeric($line[3]) ? $line[3] : 0;
                $proxyserver = is_numeric($line[4]) ? $line[4] : 0;
                $proxyaddress = mysql_real_escape_string($line[5]);
                $proxyusername = mysql_real_escape_string($line[6]);
                $proxypassword = mysql_real_escape_string($line[7]);
                
                $sql = "INSERT INTO plugin_mURLin_index (host_id, url, text_match, timeout, proxyserver, proxyaddress, proxyusername, proxypassword) VALUES ($host, '$url', '$text_match', $timeout, $proxyserver, '$proxyaddress', '$proxyusername', '$proxypassword');";
                
                if ((count($result) == 0 || $force))
                {
                    if ($verbose)
                        print "About to execute: " . $sql . "\n";

                    $result = db_execute($sql);

                    if ($result)
                    {
                        if ($verbose)
                            print "Importing line $lineNumber SUCCESS\n";

                        $processedLines++;
                    }
                    else
                        print "Importing line $lineNumber FAILED\n";
                }
            }
            else
            {
                $lineErrors++;
            }
                
        }   
    }
    else 
    {
        // error opening the file.
        return false;
    } 

    fclose($handle);
    
    if (!is_null($lines))
        $number_processed = count($lines);
    else
        $number_processed = $lineNumber;

    print "Finished importing the file $filepath
        Statistics:
            $lineNumber total lines
            $number_processed lines processed
            $processedLines records imported
            $lineErrors records with errors
            
            \n";
}


function VerifyCSV($filepath, &$lineErrors, &$lineNumber, &$lineCount, $verbose = false, $lines = null)
{
    # CSV Format must be as follows:
    # HOST,URL,TEXTMATCH,TIMEOUT,ENABLEPROXY,PROXYHOST,PROXYUSERNAME,PROXYPASSWORD

    $processedLines = 0; // How many lines have we ACTUALLY dealt with
    $duplicates = false; // Flag set to true if there are duplicate lines in
                         // the file.
    
    $handle = fopen("$filepath", "r");

    $lineNumber = 0;
    $lineCount = 0;

    if ($handle) 
    {
        # End of file flag
        $eof = false;

        print "\n\nOpening file " . $filepath . "\n";
        print "Verifying the CSV...\n";

        while (!$eof) 
        {
            $line = fgetcsv($handle);

            # Flag to denote error on a particular line            
            $err = false;

            if ($line == false)
            {
                $eof = true;
                break;
            }
            
            if (!isset($line[1]) && empty($line[0]))
            {
                # Empty line, move to next line
                
                ## Increment the counters so that we get a 'good' line
                ## but one which is completely ignored in the stats.
                $lineNumber++;
                $lineCount++;
                
                continue;
            }

            # We have a line to verify        
            $lineNumber++;
            
            # Check if lineNumber is in the requested lines (if applicable)
            if (!is_null($lines))
            {
                # We have some lines!
                if (!in_array($lineNumber, $lines))
                {
                    # We aren't interested in this line
                    
                    # To ensure this line isn't counted in the stats ensure its
                    # counted as a good (but ignored) line.
                    $lineCount++;
                    continue;
                }
            }
            
            if ($verbose)
            {
                print "\nLine: $lineNumber\n";
                print_r($line);
            }

            if (ValidateLine($line, $lineNumber))
            {
                # We have verified the line
                $lineCount++;
                $processedLines++;
                
                # Line verified check for duplicate
                $url = mysql_real_escape_string($line[1]);
                $sql = "SELECT url FROM plugin_mURLin_index WHERE url='" . $url . "';";
                
                $result = db_fetch_assoc($sql);
                
                if (count($result) > 0)
                {
                    # DUPLICATES
                    print "Warning - Line $lineNumber: The URL " . $url . " already exists in the DB.\n";
                    $duplicates = true;
                }
                              
                print $lineCount . "\r";
            }
        }   
    }
    else 
    {
        // error opening the file.
        return false;
    } 

    fclose($handle);

    $lineErrors = $lineNumber - $lineCount;
    
    # Check if there were errors on any of the lines
    if ($lineCount != $lineNumber)
    {
        print "There are $lineErrors errors in the CSV\n";
    }
    
    if (!is_null($lines))
        $number_processed = count($lines);
    else
        $number_processed = $lineNumber;

    print "Finished verifying the file $filepath
        Statistics:
            $lineNumber total lines
            $number_processed lines processed
            $processedLines records found
            $lineErrors errors found
            
            \n";
    
    if ($duplicates)
    {
        print "
            
WARNING - The script has detected that a URL which already exists in the DB is 
          being imported. Note that to force the import of these URLs please use
          the -f option.
          
";
    }
    
    return true;
}

function ValidateLine($line, $lineNumber)
{
    
    $err = false;
    
    # We have the line in the var $line
    # We need to ensure that we have 8 fields per line
    if (count($line) != 8)
    {
        print "Error - Line $lineNumber: incorrect number of fields.\n";
        $err = true;
    }

    # Timeout must be a number
    if (!is_numeric($line[3]))
    {
        print "Error - Line $lineNumber: Timeout field must be numeric.\n";
        $err = true;
    }

    # Validate URL
    if (!ValidateURL($line[1]))
    {
        print "Error - Line $lineNumber: URL is incorrectly formatted\n";
        $err = true;
    }

    if (is_numeric($line[0]))
    {
        # Check for existance of the host ID
        $sql = "SELECT id FROM host WHERE id = " . $line[0];
        $result = db_fetch_cell($sql);

        if(count($result) == 0)
        {
            # Error host doesn't exist
            print "Error - Line $lineNumber: Host " . $line[0] . " doesn't exist.\n";
            $err = true;
        }
    }
    else
    {
        # Not a host id!!
        print "Error Line $lineNumber: HostID must be numeric.\n";
        $err = true;
    }
    
    return !$err;
}

function ValidateURL($url)
{
    return preg_match("/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/", $url);
}

?>
