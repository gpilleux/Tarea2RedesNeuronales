<?php
set_time_limit(0);

require __DIR__ . '/sphinxql/vendor/autoload.php';


use \Foolz\SphinxQL\SphinxQL;
use \Foolz\SphinxQL\Connection;



const ACCENT_STRINGS = 'ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËẼÌÍÎÏĨÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëẽìíîïĩðñòóôõöøùúûüýÿ';
const NO_ACCENT_STRINGS = 'SOZsozYYuAAAAAAACEEEEEIIIIIDNOOOOOOUUUUYsaaaaaaaceeeeeiiiiionoooooouuuuyy';


function make($match){

    $conn = new Connection();
    $conn->setParams(array('host' => 'localhost', 'port' => 9306));
    try{
        // Construimos el SQL
        $query = SphinxQL::create($conn)
        ->select('vector')
        ->from('wordembEddingIndex')
        ->match('palabra', $match,true);

        $data = $query->execute();

        return $data;
    }
    catch(Exception $e){
        //echo("Excepcion handled ");//print_r($e->getMessage());
    }
}

function ponderate($sentence){
    $palSplit = explode(" ", $sentence);
    $vec4Word = array();
    $palsTotal = 0;
    
    //echo(sizeof($palSplit)." ");
    
    foreach($palSplit as $pal){
        //print_r($pal . "|");
        $arr = make($pal);
        if(sizeof($arr) > 0){
            //print_r($pal ." ");
            $palsTotal++;
            array_push($vec4Word, explode(" ", $arr[0]["vector"]));
        }
    }
    
    //echo($palsTotal." ");
    echo(100.0*$palsTotal/sizeof($palSplit)." | ");
    
    if($palsTotal > 0){

        $ponderado = array();
        for($i=0; $i<300; $i++){
            array_push($ponderado, 0);
        }
        //sumar cada componente correspondiente (0 con 0 -> almacenar en 0 | 1 con 1 -> almacenar en 1...)
        for($i=0; $i<sizeof($vec4Word); $i++){
            for($j=0; $j<300; $j++){
                $ponderado[$j] = $ponderado[$j] +  $vec4Word[$i][$j];
            }
        }
        //dividir por la cantidad de palabras cada componente
        for($i=0; $i<sizeof($ponderado); $i++){
            $ponderado[$i] = (1.0*$ponderado[$i])/$palsTotal;
        }
    }else{
        $ponderado = array();
        //delete last item pushed from $vec4Word
    }
    
    //return $ponderado;
    return implode(" ", $ponderado);
}

function readAndWrite($file_name, $file_ponderado){
    $oraciones = fopen($file_name, "r");
    
    $destination = fopen($file_ponderado, "a") or die("Unable to open file!");
    if($oraciones){
        $a = 0;
        while(($oracion = (fgets($oraciones))) !== false && $a < 1000){
            //print_r($oracion);
            $a++;
            if($a%100 == 0){
                //echo($a." ");
            }
            $oracion_fixed = trim($oracion);
            $oracion_fixed = str_replace(",", "", $oracion_fixed);
            
            //echo($oracion_fixed." | ");
            
            $ponderado = "\r\n0|".ponderate($oracion);
            //print_r($ponderado);
            fwrite($destination, $ponderado);
            //echo("writen");
        }
        fclose($destination);
        fclose($oraciones);
        echo($a);
    }else{
        echo "Unable to open " . $file_name;
    }
    
}

//print_r(make('guachimingo'));
//print_r(ponderate("esta es una oracion")[0]);

//$a = ponderate("EEUU arma x persona Tasa homicidios x armas fuegox millÃƒÂ³n Chile arma cada personas Tasax millÃƒÂ³n ar");
//print_r($a);
/*
$b = $a[0];
$c = $a[1];
$d = $a[2];
/*
echo($b[0]." + ");
echo($c[0] . " + ");
echo($d[0] . " ");


//print_r($b[0] + $c[0])
//$ad = "0|".$a;
*/


readAndWrite('arg_1000.txt', 'arg_ponderados.txt');
//print_r(make("x"));

/*
$data = "Filial de Graña y Montero se adjudica para proyecto por US millones en Chile";

foreach(mb_list_encodings() as $chr){ 
    echo mb_convert_encoding($data, 'UTF-8', $chr)." : ".$chr."<br>";    
}
*/

?>
