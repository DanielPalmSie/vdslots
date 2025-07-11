<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/phive/phive.php';
$user = cu();
$license = licJur($user);

?>
<script type="text/javascript">
    var license = '<?=$license; ?>';
    var MessageProcessor = (function () {
        return {
            test: <?= json_encode(phive('Skywind')->getSetting('test', false)); ?>,
            bosMapping: {
                "gameRoundStarted": "gameRoundStarted",
                "gameRoundEnded": "gameRoundEnded",
            },
            bosReloadBalance: function () {
                this.request({event: "refreshBalance"});

            },
            getEventType: function (event) {
                if (!event.data) return;
                var data = JSON.parse(event.data)
                if(data.msgId === 'sw2opRound') {
                    data.state === 'started' ? data.msgId = 'gameRoundStarted' : data.msgId = 'gameRoundEnded'
                }

                return data
            },
            process: function (event) {
                var data = this.getEventType(event)

                switch (data.msgId) {
                    case 'gameRoundStarted':
                        this.roundEnded = false
                        break;
                    case 'gameRoundEnded':
                        this.gameRoundEnded = true
                        break;
                    case 'sw2opLoading':
                        // this is needed only for Italy to properly load session balance
                        if(data.state === 'ended' && license === 'IT') {
                            this.reloadBalance();
                        }
                        break;
                }
            },
            reloadBalance: function () {
                this.request(JSON.stringify({msgId: 'op2swUpdateBalance'}))
            }
        };
    })();
</script>
