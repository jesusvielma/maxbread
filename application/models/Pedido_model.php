<?php

use \Illuminate\Database\Eloquent\Model as Eloquent;

class Pedido_model extends Eloquent {

    protected $table = "pedido";
	protected $primaryKey = "id_pedido";

    protected $fillable = [
        'fecha',
        'cliente_rut',
        'estado'
    ];

    protected $dates = [
        'fecha'
    ];

    public $timestamps = FALSE;

    public function productos()
    {
        return $this->belongsToMany('Producto_model','pedido_tiene_producto','id_pedido','id_producto')->withPivot('cantidad');
    }

}