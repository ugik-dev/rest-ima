<?php

if (!function_exists('MyCart')) {
    function MyCart($id)
    {
        $ci = &get_instance();

        $ci->db->select('mp_category.category_name, mp_cart.*, mp_productslist.product_name, mp_productslist.retail , mp_productslist.quantity as stock , mp_productslist.image');
        $ci->db->from('mp_cart');
        $ci->db->join('mp_productslist', 'mp_productslist.id = mp_cart.product_id');
        $ci->db->join('mp_category', 'mp_category.id = mp_productslist.category_id and mp_productslist.status != 2');
        $ci->db->join('mp_brand', "mp_brand.id = mp_productslist.brand_id");
        $res = $ci->db->get();
        return $res->result_array();
    }
}

if (!function_exists('JenisMitra')) {
    function JenisMitra()
    {
        $ci = &get_instance();

        $ci->db->select('*');
        $ci->db->from('ref_jenis_mitra');
        $res = $ci->db->get();
        return $res->result_array();
    }
}
