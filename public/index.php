<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>PHP Dump</title>
    <script src="https://unpkg.com/zlibjs@0.3.1/bin/gunzip.min.js"></script>
    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="./php-dump.js"></script>
    <script src="https://unpkg.com/babel-standalone@6/babel.min.js"></script>
    <script type="text/babel" src="./php-dump.jsx"></script>
    <link rel="stylesheet" href="./php-dump.css" />
    <link rel="stylesheet" href="https://unpkg.com/@highlightjs/cdn-assets@11.9.0/styles/mono-blue.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.9.0/highlight.min.js"></script>
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.9.0/languages/php.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/highlightjs-line-numbers.js@2.8.0/dist/highlightjs-line-numbers.min.js"></script>

</head>
<body>
<?php
include '../vendor/autoload.php';
function level3 ($origin, int $prev, float $val){
    new ExceptionThrower($val, $prev, $origin, [1,2,[3,4,[5,[6,7],8],9],0]);
}
function level2($source,int $one)
{
    level3($source,$one,pi());
}
function level1($input){
    level2($input,1);
}
class ExceptionThrower{
    public function __construct(...$args)
    {
        throw new Exception("Test exception thrown");
    }
}
ini_set('zend.exception_ignore_args', 0);
$dump = new \Mastir\PhpDump\PhpDumpBuilder(mutators: [new \Mastir\PhpDump\Mutator\RefDepthLimit(2)]);
$scope = false;
try {
    $object = new StdClass;
    $object->recursion = [$object, "mixed"];
    $object->float = pi();
    $object->max = 2147483647;

    level1($object);
} catch (\Exception $e) {
    $scope = $dump->addException($e);
}
$base64dump = base64_encode(gzencode($dump->build()));
?>
    <div id="root"></div>
    <script>
        window.dump_base64 = "<?php echo $base64dump; ?>";
        let gzip = atob(window.dump_base64);
        array = new Uint8Array(gzip.length);
        for( let i = 0; i < gzip.length; i++ ) { array[i] = gzip.charCodeAt(i) }
        let bin_array = (new Zlib.Gunzip(array)).decompress();
        let reader = new PhpDump(bin_array.buffer);
        window.dump = reader.read();
        window.onload = ()=>window.renderReactDump(document.getElementById('root'), window.dump);
    </script>
</body>
</html>
