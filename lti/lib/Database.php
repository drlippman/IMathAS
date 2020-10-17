<?php
namespace IMSGlobal\LTI;

interface Database {
    public function find_registration_by_issuer(string $iss, string $client_id);
    public function find_deployment(int $platform_id, string $deployment_id);
}

?>
