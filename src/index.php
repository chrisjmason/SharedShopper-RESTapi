<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require "../Vendor/autoload.php";
require_once dirname(__FILE__). "/helpers/DbHelper.php";

$configuration = [
    'settings' => [
        'displayErrorDetails' => true,
    ],
];

$c = new \Slim\Container($configuration);
$app = new \Slim\App($c);

$authenticate = function($request, $response, $next) use ($app){
    $db = new DbHelper();
    $apikey = $request->getHeader("apikey");

    $req_dump = print_r($apikey[0], true);
    $file = file_put_contents(addcheck, $req_dump);

    if($db->checkApikey($apikey[0])){
        $response = $next($request, $response);
        return $response;
    }else{
        $data = array('error' => true,'message' => 'apikey not valid');
        $response->withJson($data, 400);
        return $response;
    }
};

$app->post('/login', function(Request $request, Response $response){
    $data = $request->getParsedBody();

    $username = $data['username'];
    $password = $data['password'];

    $db = new DbHelper();
    $response = array();

    if($db->checkLogin($username, $password)){
        $user = $db->getUser($username);

        if($user!=NULL){
            $response["error"] = false;
            $response["message"] = "Login successfull";
            $response["apikey"] = $user["apikey"];
            $response["dataid"] = $user["dataid"];
        }else{
            $response["error"] = true;
            $response["message"] ="An error occured while logging in";
        }
    }else{
        $response["error"] = true;
        $response["message"] = "Login failed. Incorrect details.";
    }

    echo json_encode($response);
});

$app->post('/register', function(Request $request, Response $response){

    $data = $request->getParsedBody();

    $username = $data['username'];
    $password = $data['password'];

    $db = new DbHelper();
    $response1 = array();

    $result = $db->createUser($username, $password);

    if($result['status'] == USER_CREATED){
        $response1["error"] = false;
        $response1["message"] = "Successfully registered";
        $response1['apikey'] = $result['apikey'];
        $response1['dataid'] = $result['dataid'];
    }else if($result['status'] == USER_NOT_CREATED){
        $response1["error"] = true;
        $response1["message"] = "Error registering. Please try again";
    }else if($result['status'] == USER_ALREADY_EXISTS){
        $response1["error"] = true;
        $response1["message"] = "User already exists";
    }else{
        $response1["error"] = true;
        $response1["message"] = "register failed";
    }

    $newResponse = $response->withJson($response1);
});

$app->post('/updatedataid', function(Request $request, Response $response){
    $db = new DbHelper();
    $data = $request->getParsedBody();
    $response = array();

    $dataid = $data['dataId'];
    $apikey = $request->getHeader("apikey");

    if($db->updateDataid($dataid, $apikey[0])){
        $response['error'] = false;
        $response['message'] = "Share code changed!";
        $response['dataid'] = $dataid;
    }else{
        $response['error'] = true;
        $response['message'] = "Error, share code not changed";
    }
    echo json_encode($response);
})->add($authenticate);

$app->get('/getdata', function (Request $request, Response $response){

    $db = new DbHelper();
    $response = array();
    $dataid = $request->getHeader("dataid");

    $result = $db->getAllItems($dataid[0]);
    $response["items"] = array();

    while($item = mysqli_fetch_array($result)){
        $temp = array();
        $temp["title"] = $item["title"];
        $temp["description"] = $item["description"];
        $temp["colour"] = $item["colour"];
        $temp["date"] = $item["date"];
        $temp["itemcode"] = $item["itemcode"];
        $temp["dataid"] = $item["dataid"];
        $temp["timestamp"] = $item["timestamp"];
        array_push($response["items"],$temp); 
    }
    echo json_encode($response);
})->add($authenticate);

$app->post('/additem', function(Request $request, Response $response){
    $data = $request->getParsedBody();

    $title = filter_var($data['title'], FILTER_SANITIZE_STRING);
    $desc = filter_var($data['description'], FILTER_SANITIZE_STRING);
    $colour = filter_var($data['colour'], FILTER_SANITIZE_NUMBER_INT);
    $date = filter_var($data['date'], FILTER_SANITIZE_STRING);
    $code = filter_var($data['itemcode'], FILTER_SANITIZE_STRING);
    $dataid = filter_var($data['dataid'], FILTER_SANITIZE_STRING);

    $db = new DbHelper();
    $db->addItem($title,$desc,$colour,$date,$code, $dataid);
})->add($authenticate);

$app->post('/addlist', function(Request $request, Response $response){
    $data = $request->getParsedBody();
    $db = new DbHelper();
    
    foreach ($data as &$value) {
        $title = filter_var($value['title'], FILTER_SANITIZE_STRING);
        $desc = filter_var($value['description'], FILTER_SANITIZE_STRING);
        $colour = filter_var($value['colour'], FILTER_SANITIZE_NUMBER_INT);
        $date = filter_var($value['date'], FILTER_SANITIZE_STRING);
        $code = filter_var($value['itemcode'], FILTER_SANITIZE_STRING);
        $dataid = filter_var($data['dataid'], FILTER_SANITIZE_STRING);

        $db->addItem($title,$desc,$colour,$date,$code, $dataid);
    }

    echo json_encode($response);
})->add($authenticate);

$app->delete('/delete/{code}', function(Request $request, Response $response){
    $code = $request->getAttribute('code');

    $db = new DbHelper();
    $result = $db->deleteItem($code);
})->add($authenticate);

$app->post('/updatelist', function(Request $request, Response $response){
    $data = $request->getParsedBody();
    $db = new DbHelper();

    foreach ($data as &$value) {
        $code = filter_var($value['itemcode'], FILTER_SANITIZE_STRING);
        $db->deleteItem($code);
    }
})->add($authenticate);

$app->run();

?>