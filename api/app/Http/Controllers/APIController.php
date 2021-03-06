<?php

namespace App\Http\Controllers;

use Storage;
use Illuminate\Http\Request;
use Illuminate\Http\Input;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuthExceptions\JWTException;
use Carbon\Carbon;


use App\Account;
use App\AccountInformation;
use App\AccountProfile;
use App\CustomObject;
use App\Attribute;
use App\Template;
use App\Checkout;
use App\CheckoutItem;
use App\Employee;
use App\EmployeeColumn;
use App\PaymentMethod;
use App\StripeCard;
use App\PaypalTransaction;
use App\BillingInformation;
use App\Coupon;
use App\Comment;
class APIController extends Controller
{
  /*
    Author: Kennette Canales
    Version: 1.0
    Company: Cuverr
    Website: www.cuverr.com
  */
  protected $model = NULL;
  protected $foreignTable = [];
  protected $editableForeignTable = array();
  protected $requiredForeignTable = array();
  protected $response = array(
      "data" => null,
      "error" => array(),// {status, message}
      "debug" => null,
      "request_timestamp" => 0
  );

  protected $notRequired = array();
  protected $responseType = 'json'; 
  protected $rawRequest = null;
  protected $singleImageFileUpload = array();
  protected $validation = array();

  public function test()
  {
    return "Welcome to Talk Fluent Controller!";
  }

  public function response()
  {
    $this->response["request_timestamp"] = date("Y-m-d h:i:s");
    if($this->responseType == 'array'){
      return $this->response;
    }else{
      return response()->json($this->response);
    }
  }

  public function create(Request $request)
  {
    $this->rawRequest = $request;
    $this->insertDB($request->all());
    return $this->response();
  }

  public function retrieve(Request $request)
  {
    $this->rawRequest = $request;
    $this->retrieveDB($request->all());
    return $this->response();
  }

  public function update(Request $request)
  {
    $this->rawRequest = $request;
    if ($request->hasFile('image_file')){
    }
    else{
    }
    $this->updateDB($request->all());
    return $this->response();
  }

  public function delete(Request $request)
  {
    $this->deleteDB($request->all());
    return $this->response();
  }

  public function insertDB($request)
  {
    $tableColumns = $this->model->getTableColumns();
    $this->tableColumns = $tableColumns;
    if(!$this->isValid($request, "create")){
      return $this->response();
    }
    unset($tableColumns[0]);
    foreach($tableColumns as $columnName){
      if(isset($request[$columnName])){
        $this->model->$columnName = $request[$columnName];
      }else if(isset($this->defaultValue[$columnName])){
        $this->model->$columnName = $this->defaultValue[$columnName];
      }
    }
    $this->model->save();
    $childID = array();
    if($this->model->id && count($this->singleImageFileUpload)){
      for($x = 0; $x < count($this->singleImageFileUpload); $x++){
        $this->uploadSingleImageFile(
          $this->model->id,
          $this->singleImageFileUpload[$x]['name'],
          $this->singleImageFileUpload[$x]['path'],
          $this->singleImageFileUpload[$x]['column']
        );
      }
    }
    if($this->model->id && $this->editableForeignTable){
      foreach($this->editableForeignTable as $childTable){
        if(isset($request[$childTable]) && $request[$childTable]){
          $child = $request[$childTable];
          if(count(array_filter(array_keys($child), 'is_string')) > 0){//associative
            if(!isset($childID[$childTable])){
              $childID[$childTable] = array();
            }
            $child[str_singular($this->model->getTable()).'_id'] = $this->model->id;
            $foreignTable = $this->model->newModel($childTable, $child);
            foreach($child as $childKey => $childValue){
              $foreignTable->$childKey = $childValue;
            }
            $result = $this->model->find($this->model->id)->$childTable()->save($foreignTable);
            $childID[$childTable][] = $result["id"];
          }else{ // list
            foreach($child as $childValue){
              if(!isset($childID[$childTable])){
                $childID[$childTable] = array();
              }
              $childValue[str_singular($this->model->getTable()).'_id'] = $this->model->id;
              $foreignTable = $this->model->newModel($childTable, $childValue);
              foreach($childValue as $childValueKey => $childValueValue){
                if($childValueValue == null || $childValueValue == "" || empty($childValueValue)){
                  $foreignTable->$childValueKey = $childValueValue;
                }
              }
              $result = $this->model->find($this->model->id)->$childTable()->save($foreignTable);
              $childID[$childTable][] = $result["id"];
            }
          }
        }
      }
      $response = $this->model->id;
      if(count($childID)){
        $childID["id"] = $this->model->id;;
        $response = $childID;
      }
      $this->response["data"] = $response;
      return $response;
    }else{
      if($this->model->id){
        $this->response["data"] = $this->model->id;
        return true;
      }else{
        $this->response["error"]["status"] = 1;
        $this->response["error"]["message"] = "Failed to create entry in database";
        return false;
      }
    }
  }
  
  public function isValid($request, $action = "create", $subTableName = false){
    unset($this->tableColumns[0]);
    array_pop($this->tableColumns);//deleted at
    array_pop($this->tableColumns);//updated at
    array_pop($this->tableColumns);//created at
    foreach($this->tableColumns as $column){
      $this->validation[$column] = (isset($this->validation[$column])) ? $this->validation[$column] : '';
      if(!in_array($column, $this->notRequired) && !isset($this->defaultValue[$column])){//requiring all field by default
        if($action !== "update"){
          $this->validation[$column] = $this->validation[$column].($this->validation[$column] ? "| ":"")."required";
        }else if($action === "update"){
          if(in_array($column, $request)){
            $this->validation[$column] = $this->validation[$column].($this->validation[$column] ? "| ":"")."required";
          }else{

            unset($this->validation[$column]);
          }
        }
      }
    }
    if($action == "update"){
      $this->validation["id"] = "required";
      if(!isset($request["id"])){
        $this->response["error"]["status"] = 102;
        $this->response["error"]["message"] = "ID required";
        return false;
      }
    }
    if(count($this->validation)){
      foreach($this->validation as $validationKey => $validationValue){
        if($action == "update"){
          if(strpos( $validationValue, "unique" ) !== false ) { //check if rule has unique
            $rules = explode("|", $this->validation[$validationKey]);
            foreach($rules as $ruleKey => $ruleValue){ //find the unique rule
              if(strpos( $ruleValue, "unique" ) !== false){
                $rules[$ruleKey] = Rule::unique(str_replace("unique:", "", $ruleValue), $validationKey)->ignore($request["id"], "id");
              }
            }
            $this->validation[$validationKey] = $rules;
          }
        }
        if(strpos( $validationKey, "_id" ) !== false){
          $table = explode(".", str_plural(str_replace("_id", "", $validationKey)));
          $table = (count($table) > 1) ? $table[1] : $table[0];
          if(strpos( $validationKey, "parent" ) !== false){
            $table = $this->model->getTable();
          }
          $this->validation[$validationKey] = $this->validation[$validationKey]."|exists:".$table.",id";
        }
      }
      $validator = Validator::make($request, $this->validation);
      if ($validator->fails()) {
        if(!$subTableName){
          $this->response["error"]["status"] = 100;
          $this->response["error"]["message"] = $validator->errors()->toArray();
        }
        return false;
      }else{
        return true;
      }
    }
  }

  public function retrieveDB($request)
  {
      $tableName = $this->model->getTable();
      $singularTableName = str_singular($tableName);
      $tableColumns = $this->model->getTableColumns();
      if($this->requiredForeignTable){
        $this->model = $this->model->with($this->requiredForeignTable);
        for($x = 0; $x < count($this->requiredForeignTable); $x++){
          $singularForeignTable = str_singular($this->requiredForeignTable[$x]);
          $pluralForeignTable = str_plural($this->requiredForeignTable[$x]);
          $this->model = $this->model->leftJoin($pluralForeignTable, $pluralForeignTable.'.id', '=', $tableName.'.'.$singularForeignTable.'_id');
        }
      }
      if(isset($request['with_foreign_table'])){
        $foreignTable = array();
        foreach($request['with_foreign_table'] as $tempForeignTable){
          if(in_array($tempForeignTable, $this->foreignTable)){
            $foreignTable[] = $tempForeignTable;
          }
        }
        if(count($foreignTable)){
          $this->model = $this->model->with($foreignTable);
        }
      }
      if(isset($request["id"])){
         $this->model = $this->model->where($tableName.".id", "=", $request["id"]);
      }else{
        (isset($request['condition'])) ? $this->addCondition($request['condition']) : null;
        (isset($request['sort'])) ? $this->addOrderBy($request['sort']) : null;
        (isset($request['offset'])) ? $this->model->offset($request['offset']) : null;
        (isset($request['limit'])) ? $this->model = $this->model->limit($request['limit']) : null;
      }
      if(isset($request['with_soft_delete'])){
        $this->model = $this->model->withTrashed();
      }

      for($x = 0; $x < count($tableColumns); $x++){
        $tableColumns[$x] = $tableName.'.'.$tableColumns[$x];
      }
      $result = $this->model->get($tableColumns);
      if($result){
        $this->response["data"] = $result->toArray();
        if(isset($request["id"])){
          $this->response["data"] = $this->response["data"][0];
        }
      }else{
        $this->response["error"][] = [
          "status" => 200,
          "message" => "No Result"
        ];
      }
      return $result;
  }

  public function addCondition($conditions){
    /*
      column, clause, value
    */
    if($conditions){
      foreach($conditions as $condition){
        /*Table.Column, Clause, Value*/
        $condition["clause"] = (isset($condition["clause"])) ? $condition["clause"] : "=";
        $condition["value"] = (isset($condition["value"])) ? $condition["value"] : null;
        switch($condition["clause"]){
          default :
            $this->model = $this->model->where($condition["column"], $condition["clause"], $condition["value"]);
        }
      }
    }
  }
  public function addOrderBy($sort)
  {
    foreach($sort as $column => $order){
      $this->model = $this->model->orderBy($column, $order);
    }
  }



  public function updateDB($request, $noFile = false)
  {
    $responseType = isset($request['response_type']) ? $request['response_type'] : 'json';
      $tableColumns = $this->model->getTableColumns();
      $this->tableColumns = $tableColumns;
      $tableColumns = $this->model->getTableColumns();

      $this->tableColumns = $tableColumns;
      if(!$this->isValid($request, "update")){
        return $this->response();
      }
      $this->model = $this->model->find($request["id"]);
      foreach($this->tableColumns as $columnName){
        if(isset($request[$columnName])){
          $this->model->$columnName = $request[$columnName];//setting attribute
        }else if(isset($this->defaultValue[$columnName]) && isset($request[$columnName])){
          $this->model->$columnName = $this->defaultValue[$columnName];
        }

      }
      $result = $this->model->save();
      if($result && count($this->singleImageFileUpload) && !$noFile){
        $id = $this->model->id;
        // for($x = 0; $x < count($this->singleImageFileUpload); $x++){
        //   $this->uploadSingleImageFile(
        //     $id,
        //     $this->singleImageFileUpload[$x]['name'],
        //     $this->singleImageFileUpload[$x]['path'],
        //     $this->singleImageFileUpload[$x]['column'],
        //     $this->singleImageFileUpload[$x]['replace']
        //   );
        // }
      }
      if($result && $this->editableForeignTable){
        $childID = array();
        foreach($this->editableForeignTable as $childTable => $childTableValue){
          if(is_string($childTableValue)){
            $childTable = $childTableValue;
          }
          if(isset($request[$childTable]) && $request[$childTable]){
            $child = $request[$childTable];
            if(count(array_filter(array_keys($child), 'is_string')) > 0){//associative
              if(!isset($childID[$childTable])){
                $childID[$childTable] = array();
              }
              $result = false;
              if(isset($child["id"]) && $child["id"]*1) { // update
                $pk = $child["id"];
                unset($child["id"]);
                $result = $this->model->find($this->model->id)->$childTable()->where('id', $pk)->where(str_singular($this->model->getTable()).'_id', $request["id"])->update($child);
              }else if(!isset($childTableValue['no_create_on_update'])){
                $result = $this->model->find($this->model->id)->$childTable()->create($child)->id;
              }
              $childID[$childTable] = $result;
            }else{ // list
              foreach($child as $childValue){
                if(!isset($childID[$childTable])){
                  $childID[$childTable] = array();
                }
                $result = false;
                if(isset($childValue["id"]) && $childValue["id"]*1) {//update
                  $pk = $childValue["id"];
                  unset($childValue["id"]);
                  $foreignTable = $this->model->find($this->model->id)->$childTable()
                    ->where('id', $pk)
                    ->where(str_singular($this->model->getTable()).'_id', $request["id"]);
                  foreach($childValue as $childValueKey => $childValueValue){
                    if($childValueValue == null || $childValueValue == ""){
                      $foreignTable->$childValueKey = $childValueValue;
                    }
                  }
                  $result = $foreignTable
                    ->update($childValue);
                  // $foreignTable->save($foreignTable);

                }else{ //create
                  $childValue[str_singular($this->model->getTable()).'_id'] = $this->model->id;
                  $foreignTable = $this->model->newModel($childTable, $childValue);
                  // foreach($childValue as $childValueKey => $childValueValue){
                  //   if($childValueValue == null || $childValueValue == ""){
                  //     $foreignTable->$childValueKey = $childValueValue;
                  //   }
                  // }
                  $result = $this->model->find($this->model->id)->$childTable()->save($foreignTable)->id;
                }
                $childID[$childTable][] = $result;
              }
            }
          }
          if(isset($request['deleted_foreign_table'][$childTable])){
            for($x = 0; $x < count($request['deleted_foreign_table'][$childTable]); $x++){
              $this->model->find($this->model->id)->$childTable()->where('id', $request['deleted_foreign_table'][$childTable][$x])->delete();
            }
          }
        }

        $response = $this->model->id;
        if(count($childID)){
          $childID["id"] = $response;
          $response = $childID;
        }
        $this->response["data"] = $response;
        return $response;
      }else{
        if($result){
          $this->response["data"] = $result;
        }else{
          $this->response["error"] = "Failed to update entry";
        }
      }
  }

  public function deleteDB($request)
  {
    $responseType = isset($request['response_type']) ? $request['response_type'] : 'json';
    $validator = Validator::make($request, ["id" => "required"]);
    if ($validator->fails()) {
      $this->response["error"]["status"] = 101;
      $this->response["error"]["message"] = $validator->errors()->toArray();
      return false;
    }
    $this->response["data"] = $this->model->destroy($request["id"]);
  }

  public function retrieveAccountDetails($accountId){
    $result = Account::where('id', '=', $accountId)->get();
    if(sizeof($result) > 0){
      $profile = AccountProfile::where('account_id', '=', $accountId)->orderBy('created_at', 'DESC')->get();
      $information = AccountInformation::where('account_id', '=', $accountId)->get();
      $billing = BillingInformation::where('account_id', '=', $accountId)->get();
      $result[0]['profile'] = (sizeof($profile) > 0) ? $profile[0] : null;
      $result[0]['information'] = (sizeof($information) > 0) ? $information[0] : null;
      $result[0]['billing'] = (sizeof($billing) > 0) ? $billing[0] : null;
      return $result[0];
    }else{
      return null;
    }
  }

  public function getTemplateDetails($templateId){
    $result = Template::where('id', '=', $templateId)->get();
    return (sizeof($result) > 0) ? $result[0] : null;     
  }

  public function getPruchasedObjects($id){
    $result = CustomObject::where('template_id', '=', $id)->get();
    if(sizeof($result) > 0){
      $i = 0;
      foreach ($result as $key) {
        $result[$i]['attributes'] = $this->getPurchasedAttributes($result[$i]['id']);
       $i++; 
      }
    }
    return (sizeof($result) > 0) ? $result : null;
  }

  public function getPurchasedAttributes($id){
    $result = Attribute::where('payload', '=', 'object')->where('payload_value', '=', $id)->get();
    if(sizeof($result) > 0){
      return $result;
    }
    return null;
  }


  public function getObjects($id){
    $result = CustomObject::where('template_id', '=', $id)->get();
    if(sizeof($result) > 0){
      $i = 0;
      foreach ($result as $key) {
        $result[$i]['attributes'] = $this->getAttributes($result[$i]['id']);
        $result[$i]['new'] = false;
       $i++; 
      }
    }
    return (sizeof($result) > 0) ? $result : null;
  }

  public function getAttributes($id){
    $result = Attribute::where('payload', '=', 'object')->where('payload_value', '=', $id)->get();
    $response = array();
    if(sizeof($result) > 0){
      $i = 0;
      foreach ($result as $key) {
        $response[$result[$i]['attribute']] = $result[$i]['value'];
        $i++;
      }
      return $response;
    }
    return null;
  }

  public function getCheckout($payload, $payloadValue, $accountId){
    $checkout = Checkout::where('account_id', '=', $accountId)->where('status', '=', 'added')->first();
    if($checkout){
      $item = CheckoutItem::where('payload', '=', $payload)->where('payload_value', '=', $payloadValue)->where('checkout_id', '=', $checkout->id)->first();
      return ($item) ? $item : null;
    }else{
      return null;
    }
    return (sizeof($result) > 0) ? $result[0] : null;
  }

  public function getEmployee($id){
    $result = Employee::where('id', '=', $id)->get();
    if(sizeof($result) > 0){
      $result[0]['front_objects'] = $this->getObjectsCustom($result[0]['front_template'], $id);
      $result[0]['back_objects'] = $this->getObjectsCustom($result[0]['back_template'], $id);
      $result[0]['front_template_details'] = $this->getTemplateDetails($result[0]['front_template']);
      $result[0]['back_template_details'] = $this->getTemplateDetails($result[0]['back_template']);
      return $result[0];
    }
    return null;
  }

  public function getObjectsCustom($templateId, $employeeId){
      $result = CustomObject::where('template_id', '=', $templateId)->get();
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $result[$i]['attributes'] = $this->getAttributes($result[$i]['id']);
          if($result[$i]['settings'] == 'dynamic'){
            $result[$i]['content'] = $this->getNameDecoder($result[$i]['name'], $employeeId);
          }else{
            //
          }
          $result[$i]['new'] = false;
          $i++; 
        }
      }
      return (sizeof($result) > 0) ? $result : null;
    }

    public function getNameDecoder($name, $employeeId){
      $response = null;
      $cName = array('c_name', 'complete_name');
      if(in_array($name, $cName)){
        $response = $this->getEmployeeColumn('first_name', $employeeId).' '.$this->getEmployeeColumn('last_name', $employeeId);
      }else{
        $response = $this->getEmployeeColumn($name, $employeeId);
      }
      return $response;
    }

    public function getEmployeeColumn($column, $employeeId){
      $result = EmployeeColumn::where('employee_id', '=', $employeeId)->where('column', '=', $column)->get();
      return (sizeof($result) > 0) ? $result[0]['value'] : null;
    }

    public function getPaypalTransaction($id){
      $response = array();
      $result = PaypalTransaction::where('id', '=', $id)->get();
      $response['stripe'] = null;
      $response['paypal'] =  (sizeof($result) > 0) ? $result[0] : null;
      return $response;
    }

    public function getPaymentMethod($column, $value){
      $result = PaymentMethod::where($column, '=', $value)->where('status', '=', 'active')->get();
      if(sizeof($result) > 0){
        $payload = $result[0]['payload'];
        $payloadValue = $result[0]['payload_value'];
        $result[0]['stripe'] = null;
        $result[0]['paypal'] = null;
        if($payload == 'credit_card'){
          // stripe
          $cards = StripeCard::where('id', '=', $payloadValue)->first();
          $result[0]['stripe'] = ($cards) ? $cards : null;
        }else if($payload == 'paypal'){
          // paypal
        }else if($payload == 'cod'){
          // cod
        }
      }
      return (sizeof($result) > 0) ? $result[0] : null;
    }

    public function getCoupon($id){
      $result = Coupon::where('id', '=', $id)->get();
      return (sizeof($result) > 0) ? $result[0] : null;
    }

    public function getComments($payload, $payloadValue){
      $result = Comment::where('payload', '=', $payload)->where('payload_value', '=', $payloadValue)->get();
      return sizeof($result);
    }


}
