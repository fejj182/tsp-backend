<?php 

namespace App\Services;

use Google_Client;
use Google_Service_Drive;

class GoogleDrive {
    public function getFile(String $fileId)
    {
      $client = new Google_Client();
      $client->setApplicationName("Trainspotter");
      $client->setDeveloperKey(env('DRIVE_API_KEY'));

      $service = new Google_Service_Drive($client);
      $result = $service->files->get($fileId, ["alt" => "media"]);
      return $result->getBody()->getContents();
    }
}