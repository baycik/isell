<?php
$filename[]=<<<EOT
views/trade/document.html
EOT;
$search[]=<<<EOT
		Дополнительные инструменты: 

EOT;
$replace[]=<<<EOT
EOT;
$before[]=<<<EOT
EOT;
$after[]=<<<EOT
    <span class="icon-24" style="font-size: 18px;" 
        onclick="\$.post('PartyLabelFiller/fill',{doc_id:Doc.head.props.doc_id},function(ok){ok*1?alert('ГТД заполнены'):alert('Изменений не сделано');Doc.entries.reload();})"
        title="Заполнить номера ГТД">🛃</span>
EOT;
