<?php 
// This script is only to be used during testing with fake card data!
require_once __DIR__ . '/../../../phive.php';
?>
<html>
    <head>
        <script type="text/javascript" src="/phive/js/adyen.js"></script>
        <script type="text/javascript" src="/phive/js/worldpay.js"></script>

        
        <script>


         function getParams() {
             var result = {},
                 tmp = [];
             location.search
                     .substr(1)
                     .split("&")
                     .forEach(function (item) {
                         tmp = item.split("=");
                         result[tmp[0]] = decodeURIComponent(tmp[1]);
                     });
             return result;
         }

         var options = {};
         var adyenPubKey = '<?php echo phive('Cashier')->getSetting('ccard_psps')['adyen']['pub_key'] ?>';
         var worldpayPubKey = '<?php echo phive('Cashier')->getSetting('ccard_psps')['worldpay']['pub_key'] ?>';
         var cseInstance  = adyen.encrypt.createEncryption(adyenPubKey, options);

         Worldpay.setPublicKey(worldpayPubKey);

         /*
            number:          
            cvc:         
            holderName:  
            expiryMonth: 
            expiryYear:  
            generationtime:
          */
         var params = getParams();
         params['holderName'] = params['holderName'].replace(/\+/g , " ");

         var wpParams = {
             cardHolderName: params.holderName,
             cardNumber: params.number,
             expiryYear: params.expiryYear,
             expiryMonth: params.expiryMonth,
             cvc: params.cvc
         };
         
         //console.log(params);
         
         // Adyen encryption
         var cipher = cseInstance.encrypt(params);
         var wpcipher = Worldpay.encrypt(wpParams, function(errorCodes){
             console.log(errorCodes);
         });
         document.write('Adyen: '+cipher+'<br/><br/>'+'WP: '+wpcipher);

         //console.log(cipher);


        </script>

    </head>
    <body>



    </body>
</html>
