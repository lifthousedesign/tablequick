<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dashboard extends App_Controller
{
	public function __construct()
	{
		$this->models=array_merge($this->models,array(
			'patron',
		));

		$this->data['nav_pages'] = array(
			'/' => array('name' => 'Assign Seating', 'active' => ''),
			'/table_feedback' => array('name' => 'Table Feedback', 'active' => ''),
			'/guest_connection' => array('name' => 'Guest Connection', 'active' => '')
		);

		parent::__construct();
	}

	private function _load_js_css()
	{
		// Load dataTables
		$this->js[]=array(
			'file'=>'js/jquery.dataTables.min.js',
			'type'=>'plugins/datatables',
		);
		$this->css[]=array(
			'file'=>'css/jquery.dataTables.css',
			'type'=>'plugins/datatables',
		);
		// Load fancybox2
		$this->js[]=array(
			'file'=>'jquery.fancybox.pack.js',
			'type'=>'plugins/fancybox2',
		);
		$this->css[]=array(
			'file'=>'jquery.fancybox.css',
			'type'=>'plugins/fancybox2',
		);
		// Load pines notify
		$this->js[]=array(
			'file'=>'jquery.pnotify.min.js',
			'type'=>'plugins/pnotify',
		);
		$this->css[]=array(
			'file'=>'jquery.pnotify.default.css',
			'type'=>'plugins/pnotify',
		);
		$this->js[]='jquery.maskedinput.min.js';
	}

	public function index()
	{
		$this->_load_js_css();
		$this->js[]='pages/dashboard-index.js';
		$this->data['nav_pages']['/']['active'] = 'active';
	}

	public function table_feedback()
	{
		$this->_load_js_css();
		$this->data['nav_pages']['/table_feedback']['active'] = 'active';
	}

	public function guest_connection()
	{
		$this->_load_js_css();
		$this->js[]='pages/dashboard-guest-connection.js';
		$this->data['nav_pages']['/guest_connection']['active'] = 'active';
	}	

	public function refresh_guest_connection()
	{
		$this->layout=FALSE;
		$this->view=FALSE;

		$patrons = $this->patron->get_guest_connections();
		$data = array();
		foreach($patrons as $patron)
		{
			$item=array(
				$patron['id'],
				date('m/d/Y - h:ia',strtotime($patron['last_seated'])),
				$patron['name'],
				$patron['phone'],
				$patron['total_visits']	
			);

			$data[]=$item;
		}

		echo json_encode($data);
	}

	public function visit_details($id=0)
	{
		$this->_load_js_css();
		//$this->js[]='pages/dashboard-visit-details.js';
		$this->layout = FALSE; //'layouts/popup.php';
		$this->data = $this->patron->get_visit_details($id);

		//format timestamps
		foreach($this->data['visits'] as $i => $v){
			if(!$v['time_seated'])
				$seated = '-';
			else
				$seated = date('m/d/Y - h:ia',strtotime($v['time_seated']));
			$this->data['visits'][$i]['time_seated'] = $seated;
			$this->data['visits'][$i]['time_in'] = date('m/d/Y - h:ia',strtotime($v['time_in']));
		}
	}

	public function refresh_assign_seating()
	{
		$this->layout=FALSE;
		$this->view=FALSE;

		$patrons=$this->patron
			->order_by('time_in')
			->get_many_by(array(
				'user_id'=>$this->user->data['id'],
				'removed'=>0,
			));

		$data=array();

		foreach($patrons as $patron)
		{
			$item=array(
				$patron['id'],
				date('m/d/Y / h:ia',strtotime($patron['time_in'])),
				$patron['name'],
				$patron['status'],
				empty($patron['response']) ? '-' : $patron['response'],
				empty($patron['table_number']) ? '-' : $patron['table_number'],
			);

			$data[]=$item;
		}

		echo json_encode($data);
	}

	public function new_customer($id=NULL)
	{
		$this->layout=FALSE;

		if($data=$this->input->post())
		{
			$this->patron->insert(array(
				'user_id'=>$this->user->data['id'],
				'name'=>$data['name'],
				'phone'=>$data['phone'],
				'status'=>'Waiting',
				'time_in'=>$data['time_in'],
			));
			$this->view=FALSE;
			exit;
		}
		else
		{
			if($id!==NULL)
			{
				$patron=$this->patron->get($id);

				if(!empty($patron))
				{
					$this->data['patron']=$patron;
				}
			}
		}
	}

	public function remove_customer($id)
	{
		$this->layout=FALSE;

		if($patron_id=$this->input->post('id'))
		{
			$this->patron->update($patron_id,array(
				'removed'=>1,
			));
			$this->view=FALSE;
			exit;
		}
		else
		{
			$this->data['patron']=$this->patron->get($id);
		}
	}

	public function cancel_customer($id)
	{
		$this->layout=FALSE;

		if($patron_id=$this->input->post('id'))
		{
			$this->patron->update($patron_id,array(
				'status'=>'Cancelled/Left',
			));
			$this->view=FALSE;
			exit;
		}
		else
		{
			$this->data['patron']=$this->patron->get($id);	
		}
	}

	public function seat_customer($id)
	{
		$this->layout=FALSE;

		if($data=$this->input->post())
		{
			$this->patron->update($data['id'],array(
				'status'=>'Seated',
				'table_number'=>$data['table_number'],
				'time_seated'=>date('Y-m-d H:i:s'),
			));
			$this->view=FALSE;
			exit;
		}
		else
		{
			$this->data['patron']=$this->patron->get($id);	
		}
	}

	public function seat_ready($id)
	{
		$this->layout=FALSE;

		if($patron_id=$this->input->post('id'))
		{
			$patron=$this->patron->get($patron_id);

			// 141 chars
			$message='Hi, {name} - your table is now ready. Please reply with "okay", "stay at bar", or "cancel" to notify the hostess of your decision. Thank you!';
			
			if(send_sms($message,$patron,$patron['phone']))
			{
				$this->patron->update($patron_id,array(
					'status'=>'Notified'
				));
			}

			$this->view=FALSE;
			exit;
		}
		else
		{
			$this->data['patron']=$this->patron->get($id);	
		}
	}

	public function seat_next_customer()
	{
		$patron=$this->patron
			->order_by('time_in')
			->get_by(array(
				'user_id'=>$this->user->data['id'],
				'status'=>'Waiting',
				'removed'=>0,
			));

		if(!empty($patron))	
		{
			$this->view='dashboard/seat_ready';
			$this->seat_ready($patron['id']);
		}
	}

	public function process_sms()
	{
		$this->layout=FALSE;
		$this->view=FALSE;

		try
		{
			if($data=$this->input->post())
			{
				if(isset($data['From']) && isset($data['Body']))
				{
					/*
					|--------------------------------------------------------------------------
					| Find the patron that sent the text
					|--------------------------------------------------------------------------
					*/
					$from_phone=$data['From'];
					$message=trim(strtolower($data['Body']));

					// Parse out the 10-digit phone number
					$regexp='/(\+1)?(\d{10})/';
					preg_match($regexp,$from_phone,$matches);

					if(count($matches)!=3)
						throw new Exception('From phone number in an unexpected format');

					$from_phone=$matches[2]; // Should now have a phone number like 5551114444
					$from_phone=parse_phone($from_phone); // Should now have a phone number like (555) 111-4444

					if($from_phone===FALSE)
						throw new Exception('From phone number unable to be formatted');

					$patron=$this->patron
						->order_by('time_in','desc') // We want the latest entry of this patron (otherwise we may update an old entry)
						->get_by(array(
							'phone'=>$from_phone,
							'removed'=>0,
							'status'=>'Notified', // Make sure this patron has been notified and has not responded yet
						));

					if(empty($patron))
						throw new Exception('Unable to find patron');
					
					$response_keywords=$this->config->item('response_keywords');

					foreach($response_keywords as $response=>$keywords)
					{
						// Check for this response's keywords
						foreach($keywords as $keyword)
						{
							if(strpos($message,$keyword)!==FALSE)
							{
								// Found the keyword
								return $this->patron->update($patron['id'],array(
									'response'=>$response,
									'status'=>'Notified/Replied',
								));
							}
						}
					}

					// At this point, no keywords were found
					throw new Exception('Unable to determine request');
				}
				else
				{
					throw new Exception('Incorrect Twilio data posted');
				}
			}
		}
		catch (Exception $e)
		{
			// Dump the POST data
			ob_start();
			var_dump($_POST);
			$data=ob_get_clean();

			// Log it for later
			$sql='insert into sms_exception (error, data) values ('.$this->db->escape($e->getMessage()).','.$this->db->escape($data).')';
			$this->db->query($sql);
		}
	}

	private function _log($msg)
	{
		// Dump the POST data
		ob_start();
		var_dump($_POST);
		$data=ob_get_clean();

		// Log it for later
		$sql='insert into sms_exception (error, data) values ('.$this->db->escape($msg).','.$this->db->escape($data).')';
		$this->db->query($sql);
	}
}

/* End of file dashboard.php */
/* Location: ./application/controllers/dashboard.php */