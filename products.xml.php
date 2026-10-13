<?php require_once __DIR__ . '/../config.php';
header('Content-Type: application/xml; charset=utf-8');
try {
    $products = db()->query('SELECT id,name,description,category,price,stock FROM products WHERE active=1 ORDER BY id')->fetchAll();
    $xml = new XMLWriter();
    $xml->openMemory();
    $xml->startDocument('1.0', 'UTF-8');
    $xml->startElement('products');
    foreach ($products as $p) {
        $xml->startElement('product');
        foreach ($p as $key => $val) {
            $xml->writeElement($key, $val === null ? '' : (string) $val);
        }
        $xml->endElement();
    }
    $xml->endElement();
    $xml->endDocument();
    echo $xml->outputMemory();
} catch (Throwable $e) {
    http_response_code(500);
    echo '<error>Catalog unavailable</error>';
}