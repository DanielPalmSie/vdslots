<?php
require_once 'TestCasinoProvider.php';

class TestTomhorn extends TestCasinoProvider
{
    
    /**
     * Execute the command passed and outputs the response from the url that is called by the post.
     * Optionally output what is send to the url upfront.
     *
     * @param array $actions
     *
     * @return mixed Depends on the response of the requested url
     */
    public function exec($actions)
    {
        (empty($this->user_id) ? die('Please set the user ID using setUserId()') : $this->user_id);

        foreach ($actions as $key => $action) {
            $this->gp_method = $action['command'];
            $this->method = $this->provider->getWalletMethodMapping($action['command']);
            $parameters = $action['parameters'];

            $parameters['sign'] = $this->provider->generateSignFromParams($parameters, $this->user_id);
        }

        return $this->_post($parameters);
    }
    
    /**
     * Post the data in JSON format
     *
     * @param array $data An array with data to post.
     * @return mixed Outputs the response from the url that is called by the post and optionally can output what is
     *               send to the url upfront.
     *@see outputRequest()
     */
    
    protected function _post($data)
    {
        
        $encoded_data = json_encode($data);
        
        if ($this->output === true) {
            echo 'URL:' . PHP_EOL . $this->_getUrl() . PHP_EOL . "DATA:" . PHP_EOL . $encoded_data . PHP_EOL;
        }
        
        return phive()->post($this->_getUrl(), $encoded_data, 'application/json', '', $this->provider->getGameProviderName() . '-out', 'POST') . PHP_EOL;
        
    }
}
