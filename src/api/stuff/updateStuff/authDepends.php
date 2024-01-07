<?php

// auth.php

function getStuffIdFromBearerToken()
{
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'];
        $bearerToken = explode(" ", $authorizationHeader)[1];
        // Assuming the bearer token contains the stuff_id, extract it (adjust as needed)
        $decodedToken = jwt_decode($bearerToken); // Use your JWT decoding logic
        return isset($decodedToken->stuff_id) ? intval($decodedToken->stuff_id) : null;
    }
    return null;
}
