<?php
 
$filename[]=<<<EOT
views/trade/document.html
EOT;
$search[]=<<<EOT
		<span class="icon-24 icon-wallet" title="Внести оплату" onclick="Doc.trans.pay();"> </span>
EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
        <span class="icon-24 kkmintegratoricon" style="background-image: url(KKMIntegrator/cashregister.png)" title="Напечатать чек" onclick="App.loadWindow('KKMIntegrator/checkprint',{doc_id:Doc.head.props.doc_id,total:Doc.entries.footer.total})"> </span>
        <script>
            App.Topic('activeCompanySelected').subscribe(function(acomp){
                if( acomp.company_id==1 ){//TODO add id control from intagrator settings
                    $('.kkmintegratoricon').hide();
                    $('.icon-wallet').show();
                } else {
                    $('.kkmintegratoricon').show();
                    $('.icon-wallet').hide();
                }
            });
        //App.loadWindow('KKMIntegrator/advanced');
        </script>
EOT;
$after[]=<<<EOT
EOT;