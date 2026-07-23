<?php
echo "<h1>Supported Encodings</h1>";
echo "<h2>mbstring encodings:</h2>";
echo "<pre>";
print_r(mb_list_encodings());
echo "</pre>";

echo "<h2>iconv encodings:</h2>";
echo "<pre>";
print_r(iconv_get_encoding('all'));
echo "</pre>";
?>