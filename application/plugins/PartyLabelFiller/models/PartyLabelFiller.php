<?php
/* Group Name: Документ
 * User Level: 2
 * Plugin Name: Автозаполнение ГТД
 * Plugin URI: 
 * Version: 
 * Description: 
 * Author: 
 * Author URI: 
 * Trigger before: PartyLabelFiller
 */
class PartyLabelFiller extends Catalog{
    public function fill( int $doc_id ){
        $find_sql="CREATE TEMPORARY TABLE tmp_party_list AS
            SELECT 
                doc_entry_id,
                IF(se.party_label,
                    se.party_label,
                    (SELECT 
                            party_label
                    FROM
                        document_entries sub_de
                            JOIN
                        document_list sub_dl
                    WHERE
                        sub_dl.is_commited
                        AND sub_dl.doc_type=2
                        AND sub_de.product_code=de.product_code
                        AND sub_de.party_label<>''
                    ORDER BY sub_dl.cstamp DESC
                    LIMIT 1)
                    ) party
            FROM
                document_entries de
                    JOIN
                stock_entries se USING (product_code)
                    JOIN
                document_list USING(doc_id)
            WHERE
                doc_id = $doc_id 
                AND is_commited
                AND doc_type=1
                AND de.party_label = '';";
        $fill_sql="
            UPDATE
                document_entries de
                    JOIN
                tmp_party_list tpl USING(doc_entry_id)
            SET
                de.party_label=tpl.party
            WHERE
                doc_id = $doc_id;";
        $this->query($find_sql);
        $this->query($fill_sql);
        return $this->db->affected_rows();
    }
}