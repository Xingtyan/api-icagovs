<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        // 'certificado_id', // FK
        'cantidad',
        'unidad',
        'producto',
        'presentacion',
        'code_chip',
        'raza',
        'empaque',
        'sexo',       // 'Machos' | 'Hembras'
        'edad',       // en tu BD es string tipo '4 MESES'
        'valor_fob',  // decimal/número
    ];

    // Si tu tabla NO tiene 'updated_at', puedes dejar solo created_at:
    // public $timestamps = false;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    public function certificado()
    {
        return $this->belongsTo(Certificado::class, 'certificado_id');
    }
}
