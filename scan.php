<?php

    function grad($gradid,$col1,$col2,$perc,$pwidth)
    {
        $str="<linearGradient id='".$gradid."' x1='0' y1='0' x2='".$pwidth."' y2='0' gradientUnits='userSpaceOnUse' >";
        $str.="<stop offset='0%' stop-color='".$col1."' />";
        $str.="<stop offset='".$perc."%' stop-color='".$col1."' />";
        $str.="<stop offset='".($perc+1)."%' stop-color='".$col2."' />";
        $str.="</linearGradient>";
        return $str;    
    }

    function svgPropgress($pwidth,$pheight,$perc,$font,$textsize,$offs,$col1,$col2,$id)
    {
          $str="<svg xmlns='http://w3.org' viewBox='0 0 ".$pwidth." ".$pheight."' width='".$pwidth."' height='".$pheight."'>";
          $str.="<defs>";
          $str.=grad("f".$id,$col1,$col2,$perc,$pwidth);
          $str.=grad("b".$id,$col2,$col1,$perc,$pwidth);
          $str.="</defs>";
          $str.="<rect width='".$pwidth."' height='".$pheight."' fill='url(#f".$id.")' />";
          $str.="<text x='50%' y='".(($pheight*0.5)+$offs)."' font-family='".$font."' font-size='".$textsize."' dominant-baseline='top' alignment-baseline='central' text-anchor='middle' font-weight='bold' fill='url(#b".$id.")'>".$perc."%</text>";  
          $str.="</svg>";
          return $str;    
    }

    function allsubcharacters($needles,$haystack)
    {
        foreach (str_split($needles) as $needle) {
            if(!str_contains($haystack,$needle)){
                return false;
            }
        }
        return true;
    }

$files = scandir("../beeDict");

// Declarecontent arrays for building word list
$words=Array();
$cntarr=Array();
$filearr=Array();

foreach($files as $filename){
    if(substr($filename,0,4)=="2026"){
        $innerfiles=scandir("../beeDict/".$filename);
        $oldwords=count($words);
        $ifname="UNK";
        foreach($innerfiles as $innerfile){
            if(substr($innerfile,-4,4)=="json"){
                if(substr($innerfile,-6,1)=="b"){
                    $ifname=$innerfile;
                    $content=file_get_contents("../beeDict/".$filename."/".$innerfile);
                    $jsoncontent=json_decode($content);
                    $wordcount=count($jsoncontent);
                    foreach($jsoncontent as $jsonobj){
                        if(isset($words[$jsonobj->word])){
                            // Word already found. We add new items to array
                        }else{
                            // Word not found we add new item in array
                            $words[$jsonobj->word]=Array();
                        }
                        $tmparr=Array();
                        $tmparr["word"]=$jsonobj->word;
                        $tmparr["date"]=$filename;
                        $tmparr["filename"]="./".$filename."/".$innerfile;
                        $tmparr["description"]=$jsonobj->text;
                        array_push($words[$jsonobj->word],$tmparr);
                    }
                }            
            }
        }

        $wordsadded=count($words)-$oldwords;
        if($wordcount==0) $wordcount=1;
        array_push($cntarr,count($words));

        $arr=Array();
        $arr["filename"]=$filename;
        $arr["wordcount"]=$wordcount;
        $arr["wordsadded"]=$wordsadded;
        $arr["total"]=count($words);
        $arr["ifname"]=$ifname;
        array_push($filearr,$arr);
    }
}

// Read played games
foreach($filearr as &$filedata){
    // Read json file
    $ifname=str_replace("b","n",$filedata['ifname']);
    $afname=str_replace("b","u",$filedata['ifname']);

    $info=@file_get_contents("../beeDict/".$filedata['filename']."/".$ifname);
    if(!$info){
        $info=@file_get_contents("../beeDict/".$filedata['filename']."/".$afname);
        if(!$info) $info="UNK";    
    }
    $answers=Array();
    $rank="UNK";
    if($info!="UNK"){
        $jsoncontent=json_decode($info);    
        if(!isset($jsoncontent->game_data)){

        }else{
          $rank=$jsoncontent->game_data->rank;
          $answers=$jsoncontent->game_data->answers;
        }
    }
    $filedata['playanswers']=$answers;
    $filedata['rank']=$rank;
    $filedata['playanswcnt']=count($answers);
}

// Read Answers
foreach($filearr as &$filedata){

    // Read json file
    $fname=str_replace("b","a",$filedata['ifname']);
    $info=@file_get_contents("./".$filedata['filename']."/".$fname);
    if($info!="UNK"){
        $jsoncontent=json_decode($info);    
    }

    $answers=Array();
    $pangrams=Array();
    $letters="";
    if(isset($jsoncontent->answers)){
        $letters=$jsoncontent->centerLetter;
        foreach($jsoncontent->letters as $letter){
            $letters.=$letter;
        }           
        foreach($jsoncontent->answers as $answer=>$key){
            // If all characters in letters appear in answer we push to pangrams otherwise to answers
            if(allsubcharacters($letters,$answer)){
                array_push($pangrams,$answer);
            }else{
                array_push($answers,$answer);            
            }
        }
    }
    $filedata['letters']=$letters;
    $filedata['pangrams']=$pangrams;
    $filedata['answers']=$answers;
  }

  header('Content-Type: application/json');
  echo json_encode($filearr);

/*


$xmult=8;
$ymult=1.5;

echo "<div style='border:1px dotted red;'><svg width='1000' height='400' xmlns='http://www.w3.org/2000/svg'>";
$wcnt="";
$addcnt="";
$polyarea="";
$rollingavg="";
$total="";

// Forward line point
for($j=0;$j<(count($filearr));$j++){
    $wcnt.=" ".round($j*$xmult).",".round($filearr[$j]["wordcount"]*$ymult);
    $addcnt.=" ".round($j*$xmult).",".round($filearr[$j]["wordsadded"]*$ymult);
    $polyarea.=" ".round($j*$xmult).",".round($filearr[$j]["wordcount"]*$ymult);
    $total.=" ".round($j*$xmult).",".round($filearr[$j]["total"]*0.06);
    $avgcnt=0;
    $avg=0;
    for($i=($j-8);$i<($j+8);$i++){
        if($i>=0&&$i<(count($filearr))){
            $avg+=$filearr[$i]["wordsadded"];
            $avgcnt++;
        }
    }
    $rollingavg.=" ".round($j*$xmult).",".round(($avg/$avgcnt)*$ymult);
}

// Backward line point for processing of polygon
for($j=(count($filearr)-1);$j>=0;$j--){
    $polyarea.=" ".round($j*$xmult).",".round($filearr[$j]["wordsadded"]*$ymult);
}

// Filled polygon between total and added words
echo "<polygon points='$polyarea' stroke='none' stroke-width='2' fill='blue' opacity='0.3' />";

// Lines for each feature
echo "<polyline points='$wcnt' stroke='green' stroke-width='2' fill='none' opacity='0.8' stroke-linejoin='round' />";
echo "<polyline points='$addcnt' stroke='red' stroke-width='2' fill='none' opacity='0.8' stroke-linejoin='round' />";
echo "<polyline points='$rollingavg' stroke='black' stroke-width='3' fill='none' opacity='1.0' stroke-linejoin='round' />";
echo "<polyline points='$rollingavg' stroke='yellow' stroke-width='1.5' fill='none' opacity='1.0' stroke-linejoin='round' />";
echo "<polyline points='$total' stroke='gray' stroke-width='1.5' fill='none' opacity='0.5' stroke-dasharray='4 4' stroke-linejoin='round' />";

echo "</svg></div>";
*/

/*
function sortFunc($a,$b){
    return count($b)-count($a);
}


uasort($words, "sortFunc");

echo "<table>";
$cnt=0;
foreach($words as $index=>$wordarr){
    if($cnt%2==0){
        $class="upper";
    }else{
        $class="lower";
    }    
    echo "<tr class='".$class."'>";
    echo "<td rowspan='".count($wordarr)."'>".$cnt."</td>";
    echo "<td rowspan='".count($wordarr)."'>".$index."</td>";
    $i=0;
    foreach($wordarr as $word){
        if($i>0) echo "<tr class='".$class."'>";
        echo "<td>".$word['word']."</td>";
        echo "<td>".$word['date']."</td>";
        echo "<td>".$word['description']."</td>";
        echo "</tr>";
        $i++;
    }
    echo "</tr>";
    $cnt++;
}
echo "</table>";
*/ 

?>
