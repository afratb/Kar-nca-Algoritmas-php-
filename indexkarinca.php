<!DOCTYPE html>
<html>
  <head>
  </head>
  <body>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCKslLnFOxwbB5XUdTfeWUayrJit7_rj6A&callback=myMap"></script>


<?php
//eski AIzaSyD7Olhqb2AoCoj2J9HZM83HzZjnkHDBtJA

 $db = new baglanti();
 $yol = new EnKisaYol();
 $mesafeler = $yol->MesafeOlc();
 $karinca = new KarincaAlg();
 $karinca->uzaklikMatrisi = $mesafeler;
 $karinca->noktaSayisi = count($mesafeler);
 $karinca->karincaSayisi = count($mesafeler) * 0.8;
for ($k = 0; $k < $karinca->karincaSayisi; $k++)
{
    $karinca->karincalar[$k] = new Karinca();
    $karinca->karincalar[$k]->uzaklikMatrisi = $mesafeler;
}
            
 $karinca->hesapla(10, 0.8, 1.0, 5.0, 0.01, 0.5);
 $karinca->TurDuzenle();
 $yerlestirme = new OtobusYerlestirme();
 $yerlestirme->Yerlestirme($karinca->enTur,$karinca->uzaklikMatrisi);
 echo $karinca->enIyiTurUzunlugu;
 echo "<br><br>";
 for($i=0;$i<count($mesafeler);$i++){
    $yer = $karinca->enTur[$i];
    $db = new baglanti();
    if($yer==0) echo "İstanbul <br><br>";
    else
    {
        $db->sorgu("select otel_adi from hotels where id=$yer");
        while ($oku = mysqli_fetch_assoc($db->sql))
        {
        echo $oku["otel_adi"];
        echo "<br><br>";
        }
    }
}

$db->sorgu("select * from transfer");
$son=0;$i=0;
while ($oku1 = mysqli_fetch_assoc($db->sql)){
    $id=$oku1["OtoID"];
    if($son==$id){
        $kisiID=$oku1["KisiID"];
        echo "<br> $i Kisi ID = $kisiID";
        $i++; $son=$id;
    }
    else{
        $i=1;
       echo '<br><br><a href="yol.php?id='.$id.'"> Otobus ID = '.$id.' </a><br>'; 
        $kisiID=$oku1["KisiID"];
        echo "<br> $i Kisi ID = $kisiID";
        $son=$id;$i++;
    }
        
}


class baglanti
{
    public $db, $sql,$cikti;
    public function __construct() 
    {
        $this->db = @new mysqli('localhost', 'root', '', "karinca");
        if ($this->db->connect_errno) die("Hata:" . $this->db->connect_error);
        $this->db->set_charset('utf8');
    }

    public function sorgu($sorgu)
    {
        $this->sql = $this->db->query($sorgu) or die($this->db->error);
    }

    public function listele()
    {
        if ($this->sql) {
            $this->cikti = $this->sql->fetch_array();
        }
    }

    public function otelbul($id){
        $this->sql = $this->db->query("select otel_id from yolcu where sales_id=$id");
        $oku = mysqli_fetch_assoc($this->sql);
        return $oku["otel_id"];

    }

    public function __destruct() 
    {
        $this->db->close();
    }

    public function insertOtel($otel,$kuzey,$guney,$ilce){
        $this->sorgu("select max(ID) from otel");
        $oku = mysqli_fetch_assoc($this->sql);
        $son = $oku["max(ID)"]; $son++;
        $ekle = mysqli_query($this->db,"insert into otel (ID,Ad,K_Kuzey,K_Guney,Ilce) values ('$son','$otel','$kuzey','$guney','$ilce')");
        $mesafe = new EnKisaYol();
        $this->sorgu("select * from otel");
        $i=0;
        while ($oku = mysqli_fetch_assoc($this->sql))
        {
            $i++;
            $data = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$oku["K_Kuzey"].",".$oku["K_Guney"]."&destinations=".$kuzey.",".$guney."&mode=driving&language=tr-TR&key=AIzaSyD7Olhqb2AoCoj2J9HZM83HzZjnkHDBtJA";
            $json =file_get_contents($data);
            $dizi=json_decode($json,true);
            $uzak = $dizi['rows'][0]['elements'][0]['distance']['value'];
            if($i!=$son)$uzaklik[$i] = $uzak/1000;
            else $uzaklik[$i]=9999;
        }
        $this->MesafeEkle($uzaklik,$son);
    }

    public function MesafeEkle($mesafe,$id)
    {
        for($i=1;$i<=count($mesafe);$i++)
        $ekle = mysqli_query($this->db,"insert into otelmesafe (OtelID1,OtelID2,Uzaklik) values ('$id','$i','$mesafe[$i]')");
    }
///otel aralarındaki mesafeyi bulur ve veritabanına kayıt eder otel db'de id,ad,kuzey ve güney koordinatları var
    public function MesafeHesap(){
        $this->sorgu("select * from hotels");
        $i=mysqli_num_rows($this->sql); 
        for($j=0;$j<$i;$j++){
            for($k=0;$k<=$j;$k++) $oku1 = mysqli_fetch_assoc($this->sql);
            $this->sorgu("select * from hotels");                                                                                                                                                                                                 
            while ($oku2 = mysqli_fetch_assoc($this->sql))
            {
                $data = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$oku1["kuzey"].",".$oku1["guney"]."&destinations=".$oku2["kuzey"].",".$oku2["guney"]."&mode=driving&language=tr-TR&key=AIzaSyD7Olhqb2AoCoj2J9HZM83HzZjnkHDBtJA";
                $json =file_get_contents($data);
                $dizi=json_decode($json,true);
                $uzak = $dizi['rows'][0]['elements'][0]['distance']['value'];
                $uzaklik= $uzak/1000;
                if($oku1["id"]!=$oku2["id"]) $ekle = mysqli_query($this->db,"insert into uzaklik (otel_id1,otel_id2,mesafe) values (".$oku1["id"].",".$oku2["id"].",".$uzaklik.")");

                else $ekle = mysqli_query($this->db,"insert into uzaklik (otel_id1,otel_id2,mesafe) values (".$oku1["id"].",".$oku2["id"].",9999)");
            }
            $this->sorgu("select * from hotels");
        }
    
    }
}


class OtobusYerlestirme{
    
    public $ototip=40,$rezervasyon,$kisiler;

    public function OtoTip(){
        $db = new baglanti();
        $db->sorgu("select * from ototip");
        $i=0;
        while ($oku = mysqli_fetch_assoc($db->sql))
        {
            $this->otobus[$i++]=$oku["KisiSay"];
        }
    }

    public function Yerlestirme($tur,$uzaklikMatrisi){
        $db = new baglanti();
        mysqli_query($db->db,"truncate table transfer");
        $oto=1;$kisi=array();
        //$this->OtoTip();
        for($i=1;$i<count($tur);$i++){
            $db = new baglanti();
          $db->sorgu("select * from yolcu where otel_id=$tur[$i]");
            $j=1;
            while ($oku = mysqli_fetch_assoc($db->sql))
            {
                $this->rezervasyon[$i][$j] = $oku["sales_id"];
                $this->kisiler[$i][$j++] = $oku["yolcu_sayisi"];
            }
        }
        $total=0;$otelid=0;
        for($k=1;$k<=count($this->kisiler)+1;$k++){
            $ctrl=false;
            if($k==count($this->kisiler)+1){
                if($total<=$this->ototip){
                    for($t=0;$t<=count($kisi);$t++){
                        $k1 = array_pop($kisi); 
                        $otelid=$db->otelbul($k1);
                        $ekle = mysqli_query($db->db,"insert into transfer (OtelID,KisiID,OtoTipID,OtoID) values ('$otelid','$k1',1,'$oto')");$t=0;}
                }
            }
            else{
                for($j=1;$j<=count($this->kisiler[$k]);$j++){
                    $total += $this->kisiler[$k][$j];
                    array_push($kisi,$this->rezervasyon[$k][$j]);
                    if($this->ototip<$total){
                        $db = new baglanti();
                        if($j==1) $otelid=$db->otelbul($this->rezervasyon[$k-1][count($this->kisiler[$k-1])]);
                        else $otelid=$db->otelbul($this->rezervasyon[$k][$j]);
                        if($uzaklikMatrisi[$tur[$k]][$otelid]>100 && $uzaklikMatrisi[$tur[$k]][$otelid]!=9999){
                            $total -= $this->kisiler[$k][$j];$y=$k-1;array_pop($kisi);
                            $db = new baglanti();
                            for($t=0;$t<=count($kisi);$t++){
                                if($total<=$this->ototip)
                            {
                                 $k1 = array_pop($kisi); 
                                 $otelid=$db->otelbul($k1);
                                 $ekle = mysqli_query($db->db,"insert into transfer (OtelID,KisiID,OtoTipID,OtoID) values ('$otelid','$k1',1,'$oto')");$t=0;}
                            else 
                            { 
                                $k1 = array_pop($kisi); 
                                $otelid=$db->otelbul($k1);
                                $ekle = mysqli_query($db->db,"insert into transfer (OtelID,KisiID,OtoTipID,OtoID) values ('$otelid','$k1',2,'$oto')");$t=0;}
                            }
                            $total=$this->kisiler[$k][$j]; 
                            $oto++; 
                            $kisi = array(); 
                            array_push($kisi,$this->rezervasyon[$k][$j]);
                        }
                        else if($this->ototip<$total){
                            $total -= $this->kisiler[$k][$j]; array_pop($kisi);
                            for($t=0;$t<=count($kisi);$t++){
                                $k1 = array_pop($kisi); 
                                $otelid=$db->otelbul($k1);
                                $ekle = mysqli_query($db->db,"insert into transfer (OtelID,KisiID,OtoTipID,OtoID) values ('$otelid','$k1',2,'$oto')");$t=0;}
                           $kisi = array(); $oto++;
                           $total=$this->kisiler[$k][$j];
                           array_push($kisi,$this->rezervasyon[$k][$j]);
                        }
                        else if($uzaklikMatrisi[$tur[$k]][$otelid]==9999 && $this->ototip>$total)
                        continue;
                    }

                    else{
                        if($j==1 && $k!=1 ) $otelid=$db->otelbul($this->rezervasyon[$k-1][count($this->kisiler[$k-1])]);
                        else $otelid=$db->otelbul($this->rezervasyon[$k][$j]);
                        if($uzaklikMatrisi[$tur[$k]][$otelid]>100 && $uzaklikMatrisi[$tur[$k]][$otelid]!=9999){
                            $total -= $this->kisiler[$k][$j];$y=$k-1;array_pop($kisi);
                            $db = new baglanti();
                            for($t=0;$t<=count($kisi);$t++){
                                $k1 = array_pop($kisi); 
                                 $otelid=$db->otelbul($k1);
                                 $ekle = mysqli_query($db->db,"insert into transfer (OtelID,KisiID,OtoTipID,OtoID) values ('$otelid','$k1',1,'$oto')");$t=0;
                                }
                                $total=$this->kisiler[$k][$j]; 
                                $oto++; 
                                $kisi = array(); 
                                array_push($kisi,$this->rezervasyon[$k][$j]);
                            }
                    }
    
                }
            }

        }
        }
            
    }


class EnKisaYol
{
    public $distance,$rows,$mesafeler;

    public function MesafeOlc()
    {
        $verik=41.0049823;$verig=28.7319895;
        $db = new baglanti();
        $db->sorgu("select * from hotels");
        $this->rows = mysqli_num_rows($db->sql);
                $db->sorgu("select * from hotels");$j=0;
                $this->mesafeler[0][$j] = 9999;
                while ($oku = mysqli_fetch_assoc($db->sql))
                {
                    $j++;
                    $data = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$oku["kuzey"].",".$oku["guney"]."&destinations=".$verik.",".$verig."&mode=driving&language=tr-TR&key=AIzaSyD7Olhqb2AoCoj2J9HZM83HzZjnkHDBtJA";
                    $json =file_get_contents($data);
                    $dizi=json_decode($json,true);
                    $this->distance = $dizi['rows'][0]['elements'][0]['distance']['value'];
                    $this->mesafeler[0][$j] = $this->distance/1000;
                }
            {
                $db->sorgu("select * from mesafeler");
                while ($oku = mysqli_fetch_assoc($db->sql))
                {
                    $this->mesafeler[$oku["otel_id1"]][0] = $this->mesafeler[0][$oku["otel_id1"]];
                    $this->mesafeler[$oku["otel_id1"]][$oku["otel_id2"]] = $oku["mesafe"]; 
                }
            }

            //kontrol
            for($k=0;$k<count($this->mesafeler);$k++)
            for($l=0;$l<count($this->mesafeler);$l++)
              if(!isset($this->mesafeler[$k][$l]) && !isset($this->mesafeler[$l][$k]))
              $this->mesafeler[$k][$l]=9999;
              else if(!isset($this->mesafeler[$k][$l]))
              $this->mesafeler[$k][$l]=$this->mesafeler[$l][$k];
              else if($this->mesafeler[$k][$l]==0)
              {$this->mesafeler[$k][$l]=0.0001; $this->mesafeler[$l][$k]=$this->mesafeler[$k][$l];}
        return $this->mesafeler;
    }

}
 class KarincaAlg
{

    public $c = 1.0, $Q = 500,$uzaklikMatrisi, $feromonIzi = null, $olasiliklar = null, $karincalar = null;
    public $turIndisi = 0, $noktaSayisi = 0, $karincaSayisi = 0, $enIyiTur, $enIyiTurUzunlugu, $enTur;
    public $rassal;
    public $karincaYuzdesi = 0.8, $alfa = 1.0, $beta = 5.0, $rassallikFaktoru = 0.01, $buharlasmaFaktoru = 0.5;

    public function TurDuzenle(){
        for($i=0;$i<count($this->enIyiTur);$i++){
            if($this->enIyiTur[$i]==0){
                if($i==count($this->enIyiTur)-1){ //başlangıç nok en sondaysa
                    $j=0;
                    for($k=$i;$k>=0;$k--)
                    $this->enTur[$j++]=$this->enIyiTur[$k];
                }
                else if($i==0)
                $this->enTur = $this->enIyiTur;
                else{ //başlangıç nok aradaysa
                    for($s=0;$s<count($this->enIyiTur);$s++)
                    {
                        $this->enTur[$s] = $this->enIyiTur[$i++];
                        if($i==count($this->enIyiTur))$i=0;
                    }
                }
            }
        }
    }
    private function karincaAyarla()
    {
        $this->turIndisi = -1;
        for ($i = 0; $i < $this->karincaSayisi; $i++) {
            $this->karincalar[$i]->turIndisi = -1;
        }
        for ($i = 0; $i < $this->karincaSayisi; $i++) {
            
            $this->karincalar[$i]->setSayi($this->noktaSayisi);
            $this->karincalar[$i]->turIndisi = -1;
            $this->karincalar[$i]->sifirla();
            $rassal = rand(0, $this->noktaSayisi-1);
            for($k=0;$k<$i;$k++){
                if($this->karincalar[$i]->tur[0]==$rassal)
                $rassal = rand(0, $this->noktaSayisi-1);
            }
            $this->karincalar[$i]->noktaZiyaretEt($rassal);
        }
        $this->turIndisi++;
        for ($i = 0; $i < $this->karincaSayisi; $i++) {
            $this->karincalar[$i]->turIndisi++;
        }
    }

    private function karincaYurut($alfa, $beta, $rassallikFaktoru)
    {
        while ($this->turIndisi < ($this->noktaSayisi - 1)) {
            for ($i = 0; $i < $this->karincaSayisi; $i++) {
                $this->karincalar[$i]->noktaZiyaretEt($this->siradakiNoktayiSec($this->karincalar[$i], $alfa, $beta, $rassallikFaktoru));
            }
            $this->turIndisi++;
            for ($i = 0; $i < $this->karincaSayisi; $i++) {
                $this->karincalar[$i]->turIndisi++;
            }
        }
    }

    private function siradakiNoktayiSec($karinca, $alfa, $beta, $rassallikFaktoru)
    {
        $rast = mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax();
        if ($rast < $rassallikFaktoru) {
        $r = $this->rassal . rand(0, $this->noktaSayisi - $this->turIndisi -1); 
        $j = -1;
        for ($i = 0; $i < $this->noktaSayisi; $i++) { 
            if (!$karinca->ziyaretEdildiMi($i))
                $j++;
            if ($j == $r)
                return $i;
        }
    }
        return $this->olasilikHesapla($karinca, $alfa, $beta);
    }

    private function olasilikHesapla($karinca, $alfa, $beta)
    {
        $i = $karinca->tur[$karinca->turIndisi];
        $maksOlasilikIndisi = 0; 
        $maksOlasilik = 0.0;
        $olasiliklarToplami = 0.0;
        for ($j = 0; $j < $this->noktaSayisi; $j++)
            if (!$karinca->ziyaretEdildiMi($j))
                $olasiliklarToplami += pow($this->feromonIzi[$i][$j], $alfa) * pow(1.0 / $this->uzaklikMatrisi[$i][$j], $beta);

        for ($j = 0; $j < $this->noktaSayisi; $j++) {
            if ($karinca->ziyaretEdildiMi($j)) {
                $this->olasiliklar[$j] = 0.0;
            } else {
                $pay = pow($this->feromonIzi[$i][$j], $alfa) * pow(1.0 / $this->uzaklikMatrisi[$i][$j], $beta);
                $this->olasiliklar[$j] = $pay / $olasiliklarToplami;
                if ($this->olasiliklar[$j] > $maksOlasilik) {
                    $maksOlasilik = $this->olasiliklar[$j];
                    $maksOlasilikIndisi = $j;
                }
            }
        }
        return $maksOlasilikIndisi;
    }

    private function feromonGuncelle($buharlasmaFaktoru)
    {
        for ($i = 0; $i < $this->noktaSayisi; $i++)
            for ($j = 0; $j < $this->noktaSayisi; $j++)
                $this->feromonIzi[$i][$j] = $this->feromonIzi[$i][$j] * $buharlasmaFaktoru;

        for ($i = 0; $i < $this->karincaSayisi; $i++) {
            $karincaKatkisi = $this->Q / $this->karincalar[$i]->turUzunlugu();
            for ($j = 0; $j < $this->noktaSayisi - 1; $j++)
                $this->feromonIzi[$this->karincalar[$i]->tur[$j]][$this->karincalar[$i]->tur[$j + 1]] += $karincaKatkisi;
            $this->feromonIzi[$this->karincalar[$i]->tur[$this->noktaSayisi - 1]][$this->karincalar[$i]->tur[0]] += $karincaKatkisi;
        }
    }

    private function enIyiTurBelirle()
    {
        if ($this->enIyiTur == null) {
            $this->enIyiTur = $this->karincalar[0]->tur;
            $this->enIyiTurUzunlugu = $this->karincalar[0]->turUzunlugu();
        }

        for ($i = 1; $i < $this->karincaSayisi; $i++) {
            if ($this->karincalar[$i]->turUzunlugu() < $this->enIyiTurUzunlugu) {
                $this->enIyiTurUzunlugu = $this->karincalar[$i]->turUzunlugu();
                $this->enIyiTur = $this->karincalar[$i]->tur;
            }
        }
    }

    public function hesapla($iterasyon, $karincaYuzdesi, $alfa, $beta, $rassallikFaktoru, $buharlasmaFaktoru)
    {
        $oncekiEnIyiTurUzunlugu = 1.7976931348623157E308;
        for ($i = 0; $i < $this->noktaSayisi; $i++)
            for ($j = 0; $j < $this->noktaSayisi; $j++)
                $this->feromonIzi[$i][$j] = $this->c;
        for ($i = 0; $i < $iterasyon; $i++) {
            $this->karincaAyarla();
            $this->karincaYurut($alfa, $beta, $rassallikFaktoru);
            $this->feromonGuncelle($buharlasmaFaktoru);
            $this->enIyiTurBelirle();
        }
    }
}

class Karinca{

     public $tur,$ziyaretEdildiMi,$uzaklikMatrisi;

     public function noktaZiyaretEt($nokta)
     {
         $this->tur[$this->turIndisi + 1] = $nokta;
         $this->ziyaretEdildiMi[$nokta] = true;
     }

     public function ziyaretEdildiMi($nokta)
     {
         if($this->ziyaretEdildiMi[$nokta]) return $this->ziyaretEdildiMi[$nokta];
         else return false;
     }

     public function turUzunlugu() {
        $uzunluk=0;
         //$uzunluk = $this->uzaklikMatrisi[$this->tur[$this->noktaSayisi - 1]][$this->tur[0]];
            for ($i = 0; $i < $this->noktaSayisi - 1; $i++)
                $uzunluk = $uzunluk + $this->uzaklikMatrisi[$this->tur[$i]][$this->tur[$i + 1]];
            return $uzunluk;
     }

     public function sifirla() {
            for ($i = 0; $i < $this->noktaSayisi; $i++)
             {
                  $this->ziyaretEdildiMi[$i] = false;
             }  
     }

     public function setSayi($sayi){
         $this->noktaSayisi = $sayi;
     }

}
 ?>
  </body>
</html>