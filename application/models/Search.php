<?php

class Search extends Catalog{
    public function comboSearch( string $q ){
        
        
        
        
        
        
        return [
            'results'=>[
                'pcomps'=>[
                    'name'=>"Контрагенты",
                    "results"=>$this->findPassiveCompanies($q)
                ]
            ]
        ];
    }
    
    
    private function findPassiveCompanies($q){
        $pcomps=$this->Hub->load_model('Company')->listFetch($q);
        foreach($pcomps as $pcomp){
            $pcomp->title=$pcomp->label;
            $pcomp->description=$pcomp->company_name;
            $pcomp->url="javascript:App.user.pcompSelect({company_id:{$pcomp->company_id}});";
        }
        return $pcomps;
    }
}