<?php

class Pedido extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        logueado_front();
        date_default_timezone_set('America/Santiago');
        setlocale(LC_ALL, 'es_ES.UTF-8');
    }

    /**
     * Store user cart 
     */
    public function ingresar() {
        $items = $this->input->post('itemId');
        $cantItems = $this->input->post('itemCant');
        $itemOferta = $this->input->post('itemOferta');

        $usuario = $this->session->userdata('front')['correo'];

        $usuario = Usuario::where('correo',$usuario)->first();

        $date = strftime('%A');
        $dataF = substr($date,0,1);
        $dataL = substr($date,-1,1);

        $codigo= strtoupper($dataF).strtoupper($dataL).date('ymd').strtoupper(random_string('alpha',2)).date('His');

        $data = [
            'codigo_pedido' => $codigo,
            'fecha'    => \Carbon\Carbon::now(),
            'cliente_rut' => $usuario->cliente->rut,
            'estado' => 'pedido'
        ];

        $pedido = Pedido_model::create($data);

        $itemsCant = 0;

        foreach($items as $item ){
            $pedido->productos()->attach([$item => ['cantidad' => $cantItems[$item], 'oferta' => $itemOferta[$item]]]);
            $itemsCant+= $cantItems[$item];
        }

        $contenido = [
            'text' => "El cliente <strong>".$usuario->cliente->nombre."</strong> ha realizado un <strong>pedido</strong> con <strong>".$itemsCant."</strong> artículos.",
            'fecha' => $data['fecha']->toDateTimeString(),
            'avatar' => $usuario->avatar != NULL ? base_url('assets/common/uploads/profile/'.$usuario->cliente->rut.'/'.$usuario->avatar) : NULL,
        ];

        $notif = [
            'fecha' => \Carbon\Carbon::now(),
            'contenido' => json_encode($contenido),
            'estado' => 0
        ];

        Notificacion_model::create($notif);
        $this->session->set_flashdata('pedido','1');

        $this->correo_pedido($pedido->id_pedido);

        redirect('/','refresh');
    }

    public function correo_pedido($pedido)
    {
        $correo = json_decode(get_site_config_val('correo'));
        $correoAdmin = $correo->correo;

        $pedido = Pedido_model::find($pedido);
		$correo = [
			'correo' => $pedido->cliente->correo,
			'pedido' => $pedido,
			'url'	 => site_url(),
			'contenido' => (object)[
				'alertas' => [
					'noResponder' => 'Este correo es parte del sistema de notificaciones del sitio, le agradecemos no responderlo. Para cualquier duda por favor comunicate con el administrador <a href="mailto:' . $correoAdmin . '">' . $correoAdmin . '</a>.'
				]
			],
			'asunto' => $pedido->cliente->nombre.' hemos recibido tu pedido'
		];
        $this->load->library('email');

        $this->email->from('ventas@max-bread.cl', 'Ventas Max Bread');
        $this->email->to($correo['correo']);

        $this->email->subject($correo['asunto']);
        $msg = $this->slice->view('admin.email.pedido_ingresado', $correo, true);
        $this->email->message($msg);
        $this->email->set_mailtype('html');

        $this->email->send();
    }
}
