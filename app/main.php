<?php
// For debugging
error_reporting(E_ALL);

$inputFile = __DIR__ . '/inbox_file_utf8_clean.txt';
if(!is_file($inputFile) || !is_readable($inputFile)) {
  throw new RuntimeException('Failed to open: ' . $inputFile);
}

$data = file($inputFile);

// First line should contain date information
$firstLine = array_shift($data);
if(preg_match('/\d{1,2}\x20\w+\x20(?P<time>\d{2}:\d{2})\x20Id:\x20(?P<id>\d+)\b/', $firstLine, $dateMatch) === FALSE) {
  throw new RuntimeException('Failed to determine ID and time information');
}
list($time, $id) = array($dateMatch['time'], $dateMatch['id']);
unset($firstLine, $dateMatch);

// Filter out any lines that don't end in numeric values
$data = array_filter($data, function($input) {
  return preg_match('/\d$/', $input);
});

// Identify parent categories (assumes lines start with no leading whitespace)
$parents = array_values(array_filter(array_map(function($item) {
  static $lineCounter = -1;
  $lineCounter++;
  if(strpos($item, '(') === 0) {
    return array('line' => $lineCounter, 'parent' => $item);
  }

  return FALSE;
}, $data)));

// Start outputting
$delimiter = ';';
$out = fopen(__DIR__ . '/csv.txt', 'wb');
if($out === FALSE) {
  throw new RuntimeException('Failed to open output handle.');
}

fputcsv($out, array($id, $time), $delimiter);

// Process sections
/**
 * @param string $input
 * @return array
 */
function getHeaderAndCount($input) {
  $a = explode(')', $input);
  $b = array_filter(array_map('trim', explode(' ', end($a))));
  $c = (int) preg_replace('/\D/', '', array_pop($b));

  return array(implode(' ', $b), $c);
}

for($i = 0, $j = $i + 1, $k = count($parents); $i < $k; $i++, $j++) {
  if($j < $k) {
    $start = $parents[$i]['line'];
    $stop = $parents[$j]['line'];
    $slice = array_slice($data, $start, $stop - $start);
  } else {
    // last entry
    $slice = array_slice($data, $parents[$i]['line']);
  }
  $row = array();
  $formatedSlice = array_map('getHeaderAndCount', $slice);
  $header = array_shift($formatedSlice);
  foreach($header as $value) {
    $row[] = $value;
  }
  $items = array();
  foreach($formatedSlice as $child) {
    $items[] = vsprintf('%s x%d', $child);
  }
  $row[] = implode(', ', $items);
  fputcsv($out, $row, $delimiter);
}

fclose($out);