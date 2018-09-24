<!DOCTYPE html>
<html>
  <head>
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCKslLnFOxwbB5XUdTfeWUayrJit7_rj6A&callback=myMap"></script>

	<script src="https://code.jquery.com/jquery-1.12.0.min.js"></script>
    <style>
      html, body {
        height: 100%;
        margin: 0;
        padding: 0;
      }
      #map {
        height: 100%;
        float: left;
        width: 70%;
        height: 100%;
      }
#right-panel {
  font-family: 'Roboto','sans-serif';
  line-height: 30px;
  padding-left: 10px;
}

#right-panel select, #right-panel input {
  font-size: 15px;
}

#right-panel select {
  width: 100%;
}

#right-panel i {
  font-size: 12px;
}

      #right-panel {
        margin: 20px;
        border-width: 2px;
        width: 20%;
        float: left;
        text-align: left;
        padding-top: 20px;
      }
      #ozet-panel {
        margin-top: 20px;
        background-color: #FFEE77;
        padding: 10px;
      }
      #panel{
        font-size: 12px;
        text-align: left;
        min-width: 100px;
        min-height:100px;
     
      }
    </style>
  </head>
  <body>
  
    <script>
	
    $( document ).ready(function() {
      RotaCiz();
    });
    
    var gm = google.maps;
        
    function RotaCiz() {
      var directionsService = new google.maps.DirectionsService;
      var directionsDisplay = new google.maps.DirectionsRenderer;
      
      var map = new google.maps.Map(document.getElementById('map'), {
        zoom: 6,
        center: {lat: 39.287177, lng: 32.320793}
      });
      directionsDisplay.setMap(map);
      
      calculateAndDisplayRoute(directionsService, directionsDisplay); //Rota hesaplama 
      
    }
    
    function calculateAndDisplayRoute(directionsService, directionsDisplay) {
        <?php
          $id = $_GET["id"];
        $db = new baglanti();
        $dizi = array(); $i=0;
        $db->sorgu("select OtelID from transfer where OtoID=$id");
        while($oku = mysqli_fetch_assoc($db->sql)){
            if($i==0){ 
                $dizi[++$i] = $oku["OtelID"]; 
            }
            else if($dizi[$i]!=$oku["OtelID"]) $dizi[++$i] = $oku["OtelID"];
        }
        echo "var data = [";
        for($j=count($dizi);$j>1;$j--){
            $db->sorgu("select * from hotels where id=$dizi[$j]");
            $ok=mysqli_fetch_assoc($db->sql);
            echo '{ "enlem": ';
            echo  ''.$ok["kuzey"].'';
            echo ',"boylam":'; 
            echo  ''.$ok["guney"].'';
            echo " },";
        }
    
               echo '];';
                ?> 
                
                <?php

               echo "var yer = [";
        for($j=count($dizi);$j>0;$j--){
            $db->sorgu("select * from hotels where id=$dizi[$j]");
            $ok=mysqli_fetch_assoc($db->sql);
            echo '{"otel": ';
              echo  '"'.$ok["otel_adi"].'"';
              echo "},";
  

        }
    
               echo '];';
              ?>

      var waypts = [];
      var checkboxArray = document.getElementById('waypoints');
      for (var i = 0; i < data.length; i++) {
          waypts.push({
            location: { lat: data[i].enlem, lng: data[i].boylam},
            stopover: true
          });
        
      }
    
      directionsService.route({
        //Başlangıç noktası
        origin: {lat: 41.0049823, lng: 28.7319895}, 
        //Bitiş noktası
        destination: <?php 
            $db->sorgu("select * from hotels where id=$dizi[1]");
            $ok=mysqli_fetch_assoc($db->sql);
            echo "{lat: ";
            echo  ''.$ok["kuzey"].'';
            echo ", lng: ";
            echo ''.$ok["guney"].'';
            echo "},";
            
      ?>
        //Güzergah üzerindeki noktalar
        waypoints: waypts,
        optimizeWaypoints: true,
        //Rota üzerindeki hesaplamaların neye göre yapılacağı (yürüyüş,araç,bisiklet)
        travelMode: google.maps.TravelMode.DRIVING
      }, function(response, status) {
        if (status === google.maps.DirectionsStatus.OK) {
          directionsDisplay.setDirections(response);
          var route = response.routes[0];
          var ozet = document.getElementById('ozet-panel');
          ozet.innerHTML = '';
          
          
          
          // Rota bilgileri
          for (var i = 0; i < route.legs.length; i++) {
            var rota = i + 1;
            ozet.innerHTML += '<b>Otel: ' + yer[i].otel +
                '</b><br>';
            ozet.innerHTML += '<b>Mesafe:</b> ' + route.legs[i].distance.text +'<br>';
            ozet.innerHTML += '<b>Süre:</b> ' + route.legs[i].duration.text + '<br><hr><br>';
          }
        } else {
          window.alert('Rota çizilemedi ' + status);
        }
        var panel = document.getElementById('panel');
          panel.innerHTML = '';
      }
      );

    }
        </script>
<div id="map"></div>
    <div id="right-panel">
<div>
    <div id="ozet-panel"></div>
    </div>
    <div id="panel">
    </div>
<?php
$db = new baglanti();
//$db->otolist();
$db->kackisi();
?>
    </div>
        <?php

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

    public function __destruct() 
    {
        $this->db->close();
    }

    public function otolist(){
      $id=$_GET["id"];
      echo "<br><br> $id ID'li Otobüs <br><br>";
      $dizi = array();
      $this->sorgu("select KisiID from transfer where OtoID=$id");
      $i=0;
      while($oku = mysqli_fetch_assoc($this->sql)){
        $dizi[$i++]=$oku["KisiID"];
      }
      $k=1;
      for($i=0;$i<count($dizi);$i++){
        $this->sorgu("select isim from kisiler where sales_id=$dizi[$i]");
        while($oku = mysqli_fetch_assoc($this->sql)){
          echo "$k - ";
          echo ''.$oku["isim"].'';
          echo "<br>";
          $k++;
        }
      }
     

    }

    public function kackisi(){
      $id=$_GET["id"];
      echo "<br><br> $id ID'li Otobüs <br><br>";
      $dizi = array();
      $this->sorgu("select KisiID from transfer where OtoID=$id");
      $i=0;
      while($oku = mysqli_fetch_assoc($this->sql)){
        $dizi[$i++]=$oku["KisiID"];
      }
      $yolcu=0;
      for($i=0;$i<count($dizi);$i++){
      $this->sorgu("select yolcu_sayisi from yolcu where sales_id=$dizi[$i]");
      while($oku = mysqli_fetch_assoc($this->sql)){
        $yolcu += (int) $oku["yolcu_sayisi"];
      }
    }
      echo $yolcu + " kisi";
    }
}


        ?>
  </body>
</html>