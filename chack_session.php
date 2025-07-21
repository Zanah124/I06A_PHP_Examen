<?php
   header('Content-Type: application/json');
   session_start();

   $response = [
       'loggedIn' => false,
       'userName' => ''
   ];

   if (isset($_SESSION['user']) && $_SESSION['user']['id']) {
       $response['loggedIn'] = true;
       $response['userName'] = $_SESSION['user']['nom'] ?? 'Utilisateur';
   }

   echo json_encode($response);
   ?>