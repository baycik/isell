<?php
/**
 * Description of DocumentList
 *
 * @author Baycik
 */
class DocumentList extends Catalog{
    public $listFetch=['offset'=>['int',0],'limit'=>['int',50],'sortby'=>'string','sortdir'=>'(ASC|DESC)','filter'=>'json','mode'=>'string','colmode'=>'string'];
    public function listFetch($offset,$limit,$sortby='cstamp',$sortdir='DESC',$filter=null,$mode='',$colmode=''){
	$fields=['cstamp','doc_num','label'];
	if( empty($sortby) ){
	    $sortby='cstamp';
	}
	if( !in_array($sortby,$fields) ){
	    throw new Exception("Invalid sortby fieldname: ".$sortby);
	}
	$andwhere='';
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
	
	$advanced_cols="";
	$advanced_tables="";
	
	if($colmode=='advanced'){
	    
	} else {
	    
	}
	
	
	$having=$this->makeFilter($filter);
	$sql="
	    SELECT *,FORMAT(doc_total,2,'ru_RU') doc_total_frm FROM (
	    SELECT 
		doc_id,
		doc_type,
		dl.cstamp,
		doc_num,
		label pcomp_label,
		CONCAT(icon_name,' ',doc_type_name) doc_type_icon,
		doc_type_name,
		GROUP_CONCAT(CONCAT(' ',LEFT(view_name,3),view_num)) views,
		IF(is_commited,'ok Проведен','') as is_commited,
		(SELECT amount 
		    FROM 
			acc_trans 
			    JOIN 
			document_trans dtr USING(trans_id)
		    WHERE dtr.doc_id=dl.doc_id AND trans_role='total'
		    LIMIT 1) doc_total
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
	    LIMIT $limit OFFSET $offset) t";
	return $this->get_list($sql);
    }
}
