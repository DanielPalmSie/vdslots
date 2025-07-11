<?php
class ExternalVerifier
{
    public function jsonSuccess($result){
        return json_encode($this->success($result));
    }
    
    public function success($result) {
        return ['success' => true, 'result' => $result];
    }

    // TODO err_no is not used?
    public function fail($result, $err_no = 0) {
        return ['success' => false, 'result' => $result];
    }

    public function jsonFail($result, $err_no = 0){
        return json_encode($this->fail($result, $err_no));
    }

    public function getExtData($country, $nid, $req_id = null){
        // We just return success with an empty array as we don't support external lookup per default.
        return $this->success([]);
    }
}
