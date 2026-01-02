<?php
// ================= WEATHER LOGIC =================
$apiKey = "2cafdb40b77448332f086fbd8f7df0bb";   // your API key
$city   = "Patna";

$url = "https://api.openweathermap.org/data/2.5/weather?q=$city&units=metric&appid=$apiKey";
$response = @file_get_contents($url);

if ($response === FALSE) {
    echo "<span class='text-muted small'>Weather unavailable</span>";
    return;
}

$data = json_decode($response, true);

// data extract
$temp      = round($data['main']['temp']);
$condition = $data['weather'][0]['main'];
$icon      = $data['weather'][0]['icon'];
?>

<!-- ================= WEATHER UI ================= -->
<style>
.weather-widget {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 6px 10px;
  font-size: 14px;
}

.weather-icon {
  width: 34px;
  height: 34px;
}

.weather-city {
  font-weight: 600;
  line-height: 1.2;
}

.weather-meta {
  font-size: 12px;
  color: #6b7280;
}
</style>

<div class="weather-widget">
  <img src="https://openweathermap.org/img/wn/<?= $icon ?>@2x.png"
       alt="<?= htmlspecialchars($condition) ?>"
       class="weather-icon">

  <div class="weather-widget-hero">
  <div class="weather-city">
    📍 <?= htmlspecialchars($city) ?>
  </div>
  <div class="weather-meta">
    <?= $temp ?>°C · <?= htmlspecialchars($condition) ?>
  </div>
</div>

</div>


