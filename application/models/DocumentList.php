<?php
/**
 * Description of DocumentList
 *
 * @author Baycik
 */
class DocumentList extends Catalog{
    //public $listFetch=['offset'=>['int',0],'limit'=>['int',50],'sortby'=>'string','sortdir'=>'(ASC|DESC)','filter'=>'json','mode'=>'string','colmode'=>'string'];
    public function listFetch( int $offset,int $limit,string $sortby=null,string $sortdir=null,array $filter=null,string $mode=null,string $colmode=null){
	$fields=['cstamp','doc_num','label'];
	if( empty($sortby) ){
	    $sortby='cstamp';
            $sortdir='DESC';
	}
	if( !in_array($sortby,$fields) ){
	    throw new Exception("Invalid sortby fieldname: ".$sortby);
	}
	$andwhere='AND (doc_type=1 OR doc_type=2 OR doc_type=3 OR doc_type=4 OR doc_type=5 OR doc_type=-1 OR doc_type=-2) ';
	if( strpos($mode,'show_only_pcomp_docs')!==FALSE ){
	    $pcomp_id=$this->Hub->pcomp('company_id');
            if( !$pcomp_id ){
                return [];
            }
	    $andwhere.=" AND passive_company_id=$pcomp_id";
	}
	$assigned_path=  $this->Hub->svar('user_assigned_path');
	if( $assigned_path ){
	    $andwhere.=" AND path LIKE '$assigned_path%'";
	}
	$active_company_id=$this->Hub->acomp('company_id');
        
	$empty_row=null;
        if( strpos($mode,'add_empty_row')!==FALSE ){
            if( $offset==0 ){
                $limit--;//because of empty row that added at beginning
                $default_doc_extension=$this->documentExtensionDefaultGet();
                $empty_row=['doc_id'=>0,'doc_type_icon'=>"new ",'doc_extension'=>$default_doc_extension];
            } else {
                $offset--;
            }
	}
	
	$having=$this->makeFilter($filter);
	$sql="
	    SELECT 
		doc_id,
		doc_type,
                IF(doc_type=1,'DocSell',
                IF(doc_type=2,'DocBuy',
                IF(doc_type=3,'DocServiceSell',
                IF(doc_type=4,'DocServiceBuy',
                IF(doc_type=5,'DocAgentSell',
                'UnknownDocType'
                ))))) doc_extension,
		dl.cstamp,
		DATE_FORMAT(dl.cstamp,'%d.%m.%Y') doc_date_dot,
		doc_num,
		label pcomp_label,
		CONCAT(icon_name,' ',doc_type_name) doc_type_icon,
		doc_type_name,
		GROUP_CONCAT(CONCAT(' ',LEFT(view_name,3),view_num)) views,
                is_commited,
		IF(is_commited,'ok Проведен','') as commited,
		COALESCE( (SELECT amount 
		    FROM 
			acc_trans 
			    JOIN 
			document_trans dtr USING(trans_id)
		    WHERE dtr.doc_id=dl.doc_id AND dtr.trans_role='total'
		    LIMIT 1),
                    (SELECT SUM(ROUND(invoice_price*product_quantity*(1+dl.vat_rate/100),2)) FROM document_entries de WHERE de.doc_id=dl.doc_id),
                    0 )doc_total,
		(SELECT CONCAT(code,' ',descr) FROM acc_trans_status JOIN acc_trans USING(trans_status) JOIN document_trans dt USING(trans_id) WHERE dt.doc_id=dl.doc_id ORDER BY trans_id LIMIT 1) trans_status
	    FROM 
		document_list dl
		    JOIN
		companies_list cl ON passive_company_id=company_id
		    JOIN
		companies_tree ct USING(branch_id)
		    LEFT JOIN
		document_types dt USING(doc_type)
		    LEFT JOIN
		document_view_list dv USING(doc_id)
		    LEFT JOIN
		document_view_types dvt USING(view_type_id)
	    WHERE dl.active_company_id = '$active_company_id' $andwhere
	    GROUP BY doc_id
            HAVING $having
	    ORDER BY dl.is_commited,$sortby $sortdir
	    LIMIT $limit OFFSET $offset
            ";
        $rows=$this->get_list($sql);
	return $empty_row?array_merge([$empty_row],$rows):$rows;
    }
    
    public function statusFetchList(){
        $sql="SELECT * FROM document_status_list";
        return $this->get_list($sql);
    }
    
    public function documentTypeListFetch(){
        $sql="SELECT * FROM document_types WHERE doc_type<10";
        return $this->get_list($sql);
    }
    
    
    public function documentExtensionDefaultGet(){
        $pcomp_id=$this->Hub->pcomp('company_id');
        $sql="
            SELECT
                doc_handler
            FROM
                document_list
            WHERE
                passive_company_id='$pcomp_id'
            ORDER BY cstamp DESC
            LIMIT 1
            ";
        return $this->get_value($sql)??"DocumentSell";
    }
}
