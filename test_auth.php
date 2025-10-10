<?php

// Script de prueba para verificar autenticación
$baseUrl = 'http://127.0.0.1:8000/api';

// Datos de login con las credenciales proporcionadas
$loginData = [
    'email' => 'licadi1406@ofacer.com',
    'password' => 'Licadi1406@ofacer.com'
];

// Función para hacer peticiones HTTP
function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $headers[] = 'Content-Type: application/json';
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'response' => $response
    ];
}

echo "=== PRUEBA DE AUTENTICACIÓN ===\n\n";

// 1. Probar login
echo "1. Intentando hacer login con las credenciales proporcionadas...\n";
$loginResponse = makeRequest($baseUrl . '/auth/login', 'POST', $loginData);
echo "Código de respuesta: " . $loginResponse['code'] . "\n";
echo "Respuesta: " . $loginResponse['response'] . "\n\n";

if ($loginResponse['code'] === 200) {
    $loginData = json_decode($loginResponse['response'], true);
    
    if (isset($loginData['data']['access_token'])) {
        $token = $loginData['data']['access_token'];
        echo "✅ Login exitoso. Token obtenido.\n\n";
        
        // 2. Probar acceso a activos con token
        echo "2. Probando acceso a activos con token...\n";
        $headers = ['Authorization: Bearer ' . $token];
        $activosResponse = makeRequest($baseUrl . '/auth/activos', 'GET', null, $headers);
        echo "Código de respuesta: " . $activosResponse['code'] . "\n";
        echo "Respuesta: " . $activosResponse['response'] . "\n\n";
        
        // 3. Probar DELETE de un activo
        echo "3. Probando DELETE de activo con ID 10...\n";
        $deleteResponse = makeRequest($baseUrl . '/auth/activos/10', 'DELETE', null, $headers);
        echo "Código de respuesta: " . $deleteResponse['code'] . "\n";
        echo "Respuesta: " . $deleteResponse['response'] . "\n\n";
        
    } else {
        echo "❌ No se pudo obtener el token del login.\n";
    }
} else {
    echo "❌ Login falló.\n";
}

echo "=== FIN DE PRUEBA ===\n"; 