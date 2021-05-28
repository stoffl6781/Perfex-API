<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Api extends CI_Controller
{
    
    public function __construct()
    {
        parent::__construct();
        $this->load->model('leads_model');
        // retorna staff enviado no campo assigned
        if (isset($_POST['assigned'])) {
            $staff = $this->get_staff($_POST['assigned']);
            $fakelogin = $this->fakelogin($staff[0]['email'], $staff[0]['password']);
            if (!$fakelogin) {
                echo "Erro";
            }
        }
        
        

        
    }

    //
    function get_staff($assigned)
    {
        $staff = $this->db->query("SELECT * FROM `tblstaff` WHERE `staffid` = '{$assigned}' LIMIT 1")->result_array();
        return $staff;
    }

    // simula um login com o email e o hash da senha
    function fakelogin($email, $password, $remember = null, $staff = true)
    {
        if ((!empty($email)) and (!empty($password))) {
            $table = db_prefix() . 'contacts';
            $_id = 'id';
            if ($staff == true) {
                $table = db_prefix() . 'staff';
                $_id = 'staffid';
            }
            $this->db->where('email', $email);
            $user = $this->db->get($table)->row();

            if ($user) {
                // Email is okey lets check the password now
                if ($password != $user->password) {
                    hooks()->do_action('failed_login_attempt', [
                        'user' => $user,
                        'is_staff_member' => $staff,
                    ]);

                    log_activity('Failed Login Attempt [Email: ' . $email . ', Is Staff Member: ' . ($staff == true ? 'Yes' : 'No') . ', IP: ' . $this->input->ip_address() . ']');

                    // Password failed, return
                    return false;
                }
            } else {

                hooks()->do_action('non_existent_user_login_attempt', [
                    'email' => $email,
                    'is_staff_member' => $staff,
                ]);

                log_activity('Non Existing User Tried to Login [Email: ' . $email . ', Is Staff Member: ' . ($staff == true ? 'Yes' : 'No') . ', IP: ' . $this->input->ip_address() . ']');

                return false;
            }

            if ($user->active == 0) {
                hooks()->do_action('inactive_user_login_attempt', [
                    'user' => $user,
                    'is_staff_member' => $staff,
                ]);
                log_activity('Inactive User Tried to Login [Email: ' . $email . ', Is Staff Member: ' . ($staff == true ? 'Yes' : 'No') . ', IP: ' . $this->input->ip_address() . ']');

                return [
                    'memberinactive' => true,
                ];
            }

            $twoFactorAuth = false;
            if ($staff == true) {
                $twoFactorAuth = $user->two_factor_auth_enabled == 0 ? false : true;

                if (!$twoFactorAuth) {
                    hooks()->do_action('before_staff_login', [
                        'email' => $email,
                        'userid' => $user->$_id,
                    ]);

                    $user_data = [
                        'staff_user_id' => $user->$_id,
                        'staff_logged_in' => true,
                    ];
                } else {
                    $user_data = [];
                    if ($remember) {
                        $user_data['tfa_remember'] = true;
                    }
                }
            } else {
                hooks()->do_action('before_client_login', [
                    'email' => $email,
                    'userid' => $user->userid,
                    'contact_user_id' => $user->$_id,
                ]);

                $user_data = [
                    'client_user_id' => $user->userid,
                    'contact_user_id' => $user->$_id,
                    'client_logged_in' => true,
                ];
            }

            $this->session->set_userdata($user_data);

            return true;
        }

        return false;
    }

    // retorna campos customizados
    public function return_customfields($param, $id)
    {
        if ($param == 1) {
            $result = $this->db->query("SELECT* FROM tblcustomfields ")->result();

            if ($result) {
                $data = array();
                foreach ($result as $row) {
                    $data[] = $row->slug;
                }
                echo json_encode([
                    'success' => true,
                    'code' => '200',
                    'message' => 'os slugs dos campos globais: ',
                    'slugs' => $data
                ], JSON_UNESCAPED_UNICODE);
                die;
            }
        } else {
            // se não informar retorna do
            $camposPersonalizados = $this->db->query("SELECT * FROM `tblcustomfieldsvalues` INNER JOIN `tblcustomfields` ON tblcustomfieldsvalues.fieldid = tblcustomfields.id WHERE tblcustomfieldsvalues.relid = '{$id}' ")->result_array();
            $slugCamposPersonalizados = [];
            foreach ($camposPersonalizados as $row) {
                $slugCamposPersonalizados[] = $row['slug'];
                $slugCamposPersonalizados[] = $row['value'];
            }
            // $slugCamposPersonalizados = implode(',', $slugCamposPersonalizados);
            if (!empty($slugCamposPersonalizados)) {
                echo json_encode([
                    'success' => true,
                    'code' => '200',
                    'message' => 'os slugs dos campos personalizados do cliente: ',
                    'slugs' => $slugCamposPersonalizados,

                ], JSON_UNESCAPED_UNICODE);
                die;
            } else {
                echo json_encode([
                    'success' => false,
                    'code' => '200',
                    'message' => 'o cliente não possui campos personalizados',
                ], JSON_UNESCAPED_UNICODE);
                die;
            }
        }
    }

    // retorna campos ativos
    function activefields()
    {
        $sources = $this->db->query("SELECT * FROM `tblleads_sources` ")->result();
        foreach ($sources as $sources) {
            $sources_field[] = $sources->name;
        }
        $status = $this->db->query("SELECT * FROM `tblleads_status` ")->result();
        foreach ($status as $status) {

            $status_field[] = $status->name;
        }
        $staff = $this->db->query("SELECT * FROM `tblstaff`")->result();
        foreach ($staff as $staff) {
            $staff_field[] = $staff->firstname . ' ' . $staff->lastname;
        }
        return json_encode([
            'active_fields' => [
                'sources' => $sources_field,
                'status' => $status_field,
                'staff_field' => $staff_field,
            ]
        ], JSON_UNESCAPED_UNICODE);
    }

    // retorna campos do lead
    function returninfo($get_lead, $param)
    {
        if ($param == 1) {
            return json_encode([
                'success' => true,
                'code' => '200',
                'message' => $get_lead,
            ], JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode([
                'success' => true,
                'code' => '200',
                'message' => 'os dados do cliente requisitado é: ',
                'id' => $get_lead['id'],
                'status' => $get_lead['status'],
                'source' => $get_lead['source'],
                'assigned' => $get_lead['assigned']
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /*  custom fields   */

    // função responsavel por
    function CustomField($post_data, $id)
    {
        // percorro todos os campos enviados
        foreach ($post_data as $key => $value) {
            if ($this->findCustomField($key)) {
                if ($this->findLeadCustomField($key, $id)) {
                    $this->updateCustomField($key, $value, $id);
                } else {
                    $this->addCustomField($key, $value, $id);
                }

            }
        }
    }

    // verifica se e customfield
    function findCustomField($key)
    {
        $camposEsperados = [
            'token',
            'source',
            'status',
            'tags',
            'assigned',
            'name',
            'email',
            'website',
            'phonenumber',
            'company',
            'address',
            'city',
            'state',
            'country',
            'zip',
            'default_language',
            'custom_contact_date',
            'contacted_today',
            'description',
            'title',
            'idcustomfield',
            'valorcustomfield',
            'returnactivefields',
            'returncustomfields',
            'returninfo',
            'blockcreatelead',
            'blockupatelead',
        ];
        // verifico se algum dos campos não está nos padrões dos campos esperados
        if (!in_array($key, $camposEsperados)) {
            return true;
        } else {
            return false;
        }
    }

    // verifica se e customfield
    function findLeadCustomField($key, $id)
    {

        $findCustomField = $this->db->query("SELECT * FROM `tblcustomfieldsvalues` JOIN `tblcustomfields` ON tblcustomfieldsvalues.fieldid = tblcustomfields.id WHERE tblcustomfields.`slug` = '" . $key . "' AND tblcustomfieldsvalues.`relid` = '" . $id . "' ")->row_array();

        if ($findCustomField) {
            return true;
        } else {
            return false;
        }

    }

    // adiciona campo customizado
    function addCustomField($key, $value, $id)
    {
        $data = [
            'relid' => $id,
            'fieldid' => $this->getSlug($key),
            'fieldto' => 'leads',
            'value' => $value
        ];
        $this->db->insert('tblcustomfieldsvalues', $data);
    }

    // atualiza campo customizado
    function updateCustomField($key, $value, $id)
    {
        if (!empty($value)) {
            $data = [
                'value' => $value
            ];
            $this->db->where('relid', $id);
            $this->db->where('fieldid', $this->getSlug($key));
            $this->db->update('tblcustomfieldsvalues', $data);
        }
    }

    // retorna o id
    function getSlug($key)
    {
        $slug = $this->db->query("SELECT * FROM `tblcustomfields` WHERE `slug` = '{$key}' ")->row();
        if ($slug) {
            return $slug->id;
        }
    }

    /*  countries  */

    // aqui retorna o country_id pelo short_name
    function get_countries($country_name)
    {
        $country = $this->db->query("SELECT * FROM `tblcountries` WHERE `short_name` = '{$country_name}' ")->row();
        if ($country) {
            return $country->country_id;
        }
    }

    /*  leads  */

    /* função responsável por tratar e criar novo lead */
    public function createLead($post_data)
    {
        $staff = $this->get_staff($post_data['assigned']);
        // parece que evita
        $GLOBALS['current_user'] = new stdClass();
        $GLOBALS['current_user']->email = $staff[0]['email'];
        $GLOBALS['current_user']->firstname = $staff[0]['firstname'];
        $GLOBALS['current_user']->lastname = $staff[0]['lastname'];
        //var_dump($GLOBALS['current_user']);

        if (empty($post_data['source']) || empty($post_data['status']) || empty($post_data['name']) || empty($post_data['email'])) {
            $message = 'Erro, é necessario enviar os campos requeridos (name, email, status, source) para criar um lead';

            // retorno json
            return json_encode([
                'success' => false,
                'code' => '200',
                'id' => $id,
                'message' => $message,
            ], JSON_UNESCAPED_UNICODE);
        }

        $add_data = [
            'source' => $post_data['source'],
            'status' => $post_data['status'],
   //         'tags' => empty($post_data['tags']) ? '' : str_replace("-", "", $post_data['tags']),
            'assigned' => empty($post_data['assigned']) ? '-' : $post_data['assigned'],
            'name' => $post_data['name'],
            'email' => $post_data['email'],
            'website' => empty($post_data['website']) ? '-' : $post_data['website'],
            'phonenumber' => empty($post_data['phonenumber']) ? '-' : str_replace('\s', '', $post_data['phonenumber']),
            'company' => empty($post_data['company']) ? '-' : $post_data['company'],
            'address' => empty($post_data['address']) ? '-' : $post_data['address'],
            'city' => empty($post_data['city']) ? '-' : $post_data['city'],
            'state' => empty($post_data['state']) ? '-' : $post_data['state'],
            'country' => empty($post_data['country']) ? '-' : $this->get_countries(strtolower($post_data['country'])),
            'zip' => empty($post_data['zip']) ? '-' : str_replace('-', '', $post_data['zip']),
            'default_language' => empty($post_data['default_language']) ? '-' : $post_data['default_language'],
            'custom_contact_date' => '',
            'contacted_today' => 'on',
            'description' => empty($post_data['description']) ? '-' : $post_data['description'],
            'title' => empty($post_data['title']) ? '-' : $post_data['title']
        ];

        $id = $this->leads_model->add($add_data);

        $message = $id ? 'Lead created' : '';

        $this->CustomField($post_data, $id);

        // retorno do create
        return json_encode([
            'success' => $id ? true : false,
            'code' => '200',
            'id' => $id,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE);
    }

    /* função responsável por tratar e criar novo lead */
    public function updateLead($post_data, $dadosExistentes, $id)
    {
        $staff = $this->get_staff($post_data['assigned']);
        // parece que evita
        $GLOBALS['current_user'] = new stdClass();
        $GLOBALS['current_user']->email = $staff[0]['email'];
        $GLOBALS['current_user']->firstname = $staff[0]['firstname'];
        $GLOBALS['current_user']->lastname = $staff[0]['lastname'];
        //var_dump($GLOBALS['current_user']);

        // Tags que o cliente possui
        $tags = $this->db->query("SELECT * FROM tbltaggables WHERE rel_id = " . $id)->result_array();
        $valoresTags = [];
        // verifica se não retornou nulo
        if ($tags) {
            foreach ($tags as $key => $value) {
                // $row = $this->db->query("SELECT name FROM tbltags WHERE id = '{$value['tag_id']}' ")->result_array();
                $valoresTags[] = current($this->db->query("SELECT name FROM tbltags WHERE id = " . $value['tag_id'])->result_array())['name'];

                // $valoresTags[] = current($row['name']);
            }

        }
        $valorTags = [];
        $tagsenviadas = empty($post_data['tags']) ? '' : explode(',', $post_data['tags']);
        // identifica se há alguma tag para remover
        $tagsParaRemover = [];
        $tagsParaMerge = [];
        if ($tagsenviadas) {
            foreach ($tagsenviadas as $chave => $valor) {
                $caractere = substr(ltrim(rtrim($valor)), 0, 1);
                if ($caractere == '-') {
                    $restanteString = substr($valor, 1, strlen($valor));
                    if (strlen($restanteString) > 0) {
                        $tagsParaRemover[] = $restanteString;
                    }
                } else {
                    $tagsParaMerge[] = $valor;
                }
            }

            // percorre as tags existentes do cliente
            $valoresTags = array_merge($valoresTags, $tagsParaMerge);
            foreach ($valoresTags as $key => $value) {
                if ($value == '-') continue;
                if (!empty($tagsParaRemover) && in_array($value, $tagsParaRemover)) {
                    continue;
                }
                $valorTags[] = $value;
            }
            //$valorTags = implode(',', $valorTags);
        }

        if (isset($post_data['source']) && empty($post_data['source'])) {
            $source = $dadosExistentes['source'];
        } elseif (isset($post_data['source']) && !empty($post_data['source'])) {
            $source = $post_data['source'];
        } else {
            $source = $dadosExistentes['source'];
        }

        if (isset($post_data['position']) && empty($post_data['position'])) {
            $position = $dadosExistentes['title'];
        } elseif (isset($post_data['position']) && !empty($post_data['position'])) {
            $position = $post_data['position'];
        } else {
            $position = $dadosExistentes['title'];
        }

        if (isset($post_data['status']) && empty($post_data['status'])) {
            $status = $dadosExistentes['status'];
        } elseif (isset($post_data['status']) && !empty($post_data['status'])) {
            $status = $post_data['status'];
        } else {
            $status = $dadosExistentes['status'];
        }

        if (isset($post_data['tags']) && empty($post_data['tags'])) {
            $valorTags = $dadosExistentes['tags'];
        } elseif (isset($post_data['tags']) && !empty($post_data['tags'])) {
            $valorTags = implode(',', $valorTags);
        } else {
            $valorTags = $dadosExistentes['tags'];
        }

        if (isset($post_data['assigned']) && empty($post_data['assigned'])) {
            $assigned = $dadosExistentes['assigned'];
        } else if (isset($post_data['assigned']) && !empty($post_data['assigned'])) {
            $assigned = $post_data['assigned'];
        } else {
            $assigned = $dadosExistentes['assigned'];
        }

        if (isset($post_data['name']) && empty($post_data['name'])) {
            $name = $dadosExistentes['name'];
        } elseif (isset($post_data['name']) && !empty($post_data['name'])) {
            $name = $post_data['name'];
        } else {
            $name = $dadosExistentes['name'];
        }

        if (isset($post_data['email']) && empty($post_data['email'])) {
            $email = $dadosExistentes['email'];
        } elseif (isset($post_data['email']) && !empty($post_data['email'])) {
            $email = $post_data['email'];
        } else {
            $email = $dadosExistentes['email'];
        }

        if (isset($post_data['website']) && empty($post_data['website'])) {
            $website = $dadosExistentes['website'];
        } else if (isset($post_data['website']) && !empty($post_data['website'])) {
            $website = $post_data['website'];
        } else {
            $website = $dadosExistentes['website'];
        }

        if (isset($post_data['phonenumber']) && empty($post_data['phonenumber'])) {
            $phonenumber = $dadosExistentes['phonenumber'];
        } else if (isset($post_data['phonenumber']) && !empty($post_data['phonenumber'])) {
            $phonenumber = str_replace('\s', '', $post_data['phonenumber']);
        } else {
            $phonenumber = $dadosExistentes['phonenumber'];
        }

        if (isset($post_data['company']) && empty($post_data['company'])) {
            $company = $dadosExistentes['company'];
        } elseif (isset($post_data['company']) && !empty($post_data['company'])) {
            $company = $post_data['company'];
        } else {
            $company = $dadosExistentes['company'];
        }

        if (isset($post_data['address']) && empty($post_data['address'])) {
            $address = $dadosExistentes['address'];
        } else if (isset($post_data['address']) && !empty($post_data['address'])) {
            $address = $post_data['address'];
        } else {
            $address = $dadosExistentes['address'];
        }

        if (isset($post_data['city']) && empty($post_data['city'])) {
            $city = $dadosExistentes['city'];
        } else if (isset($post_data['city']) && !empty($post_data['city'])) {
            $city = $post_data['city'];
        } else {
            $city = $dadosExistentes['city'];
        }
        if (isset($post_data['state']) && empty($post_data['state'])) {
            $state = $dadosExistentes['state'];
        } else if (isset($post_data['state']) && !empty($post_data['state'])) {
            $state = $post_data['state'];
        } else {
            $state = $dadosExistentes['state'];
        }
        if (isset($post_data['country']) && empty($post_data['country'])) {
            $country = $dadosExistentes['country'];
        } elseif (isset($post_data['country']) && !empty($post_data['country'])) {
            $country = $this->get_countries(strtolower($post_data['country']));
        } else {
            $country = $dadosExistentes['country'];
        }
        if (isset($post_data['zip']) && empty($post_data['zip'])) {
            $zip = $dadosExistentes['zip'];
        } elseif (isset($post_data['zip']) && !empty($post_data['zip'])) {
            $zip = str_replace('-', '', $post_data['zip']);
        } else {
            $zip = $dadosExistentes['zip'];
        }
        if (isset($post_data['default_language']) && empty($post_data['default_language'])) {
            $default_language = $dadosExistentes['default_language'];
        } else if (isset($post_data['default_language']) && !empty($post_data['default_language'])) {
            $default_language = $post_data['default_language'];
        } else {
            $default_language = $dadosExistentes['default_language'];
        }
        if (isset($post_data['description']) && empty($post_data['description'])) {
            $description = $dadosExistentes['description'];
        } elseif (isset($post_data['description']) && !empty($post_data['description'])) {
            $description = $dadosExistentes['description'] . '<br>' . $post_data['description'];
        } else {
            $description = $dadosExistentes['description'];
        }
        if (isset($post_data['title']) && empty($post_data['title'])) {
            $title = $dadosExistentes['title'];
        } else if (isset($post_data['title']) && !empty($post_data['title'])) {
            $title = $post_data['title'];
        } else {
            $title = $dadosExistentes['title'];
        }

        $add_data = [
            'source' => $source,
            'status' => $status,
           // 'tags' => $valorTags,
            'assigned' => $assigned,
            'name' => $name,
            'email' => $email,
            'website' => $website,
            'phonenumber' => $phonenumber,
            'company' => $company,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'country' => $country,
            'zip' => $zip,
            'default_language' => $default_language,
            'custom_contact_date' => '',
            'contacted_today' => 'on',
            'description' => $description,
            'title' => $title,
        ];

        if (!isset($post_data['tags'])) {
            unset($post_data['tags']);
            unset($add_data['tags']);
        }
        if (empty($post_data['tags'])) {
            unset($post_data['tags']);
            unset($add_data['tags']);
        }
        unset($add_data['custom_contact_date'], $add_data['contacted_today']);

        $emailOriginal = $this->db->select('email')->where('id', $id)->get('tblleads')->row()->email;
        $proposalWarning = false;
        $message = '';
        $success = $this->leads_model->update($add_data, $id);

        $this->CustomField($post_data, $id);

        //	echo $this->db->last_query();

        if ($success || !$CustomField || $CustomField) {
            $emailNow = $this->db->select('email')->where('id', $id)->get('tblleads')->row()->email;
            $proposalWarning = (total_rows('tblproposals', [
                    'rel_type' => 'lead',
                    'rel_id' => $id,]) > 0 && ($emailOriginal != $emailNow) && $emailNow != '') ? true : false;
            $message = "Lead updated";

            // retorno do update
            return json_encode([
                'success' => $success,
                'code' => '200',
                'message' => $message,
                'id' => $id,
                'proposal_warning' => $proposalWarning,
            ], JSON_UNESCAPED_UNICODE);


        } elseif (!$CustomField || $CustomField) {
            $message = "Lead updated";

            // retorno do update
            return json_encode([
                'success' => $success,
                'code' => '200',
                'message' => $message,
                'id' => $id,
                'proposal_warning' => $proposalWarning,
            ], JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode([
                'success' => $success,
                'code' => '200',
                'message' => "Erro, falha ao atualizar",
                'id' => $id,
                'proposal_warning' => $proposalWarning,
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    // recebe envio do endpoint
    public function index()
    {
        if (isset($_POST)) {
            $post_data = $_POST;
            // aqui recebe o token
            $token=get_option('api_token');	
            if ($post_data['form-field-token'] || $post_data['token'] != $token) {
                $token_send=$post_data['token'].$post_data['form-field-token'];
                echo json_encode([
                    'error' => 1,
                    'message' => 'token invalid',
                    'sent_token' => $post_data['token']
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // retorna lead pelo email    
            $get_lead_by_email = $this->db->query("SELECT * FROM `tblleads` WHERE `email` = '{$post_data['email']}' ")->result_array();
            $id = $get_lead_by_email[0]['id'];


            // retorna campos ativos
            if (isset($post_data['returnactivefields']) && $post_data['returnactivefields'] == 1) {
                echo $this->activefields();
                die;
            }

            // peguei campos personalizados
            if (isset($post_data['returncustomfields']) && $post_data['returncustomfields'] == 1) {
                // se não foi incluido campo email retorna global
                if (isset($post_data['email'])) {
                    echo $this->return_customfields(0, $id);
                    die;
                } else {
                    echo $this->return_customfields(1, $id);
                    die;
                }
            }

            // retorna dados do lead
            if (isset($post_data['returninfo'])) {
                if (isset($post_data['value']) && $post_data['value'] == 1) {
                    echo $this->returninfo($get_lead_by_email[0], 1);
                    die;
                } else {
                    echo $this->returninfo($get_lead_by_email[0], 0);
                    die;
                }
            }

            // verifica se foi o inibidor de leads foi enviado e é 1
            if (isset($post_data['blockcreatelead']) && $post_data['blockcreatelead'] == 1) {
                if (is_numeric($id) && $id > 0) {
                    // se o id não esta vazio atualiza o lead
                    echo $this->updateLead($post_data, $get_lead_by_email[0], $id);
                    die;
                } else {
                    // se estiver vazio retorna erro
                    //echo $this->returnError();
                    echo json_encode([
                        'success' => 'false',
                        'message' => 'o campo block create lead com o valor 1 inibe a criação do lead',
                        'code' => '200',
                        'id' => '',
                        'proposal_warning' => '',
                    ], JSON_UNESCAPED_UNICODE);
                    die;
                }
            } // verifica se foi o inibidor de leads foi enviado
            elseif (isset($post_data['blockcreatelead'])) {
                // verifica se foi o inibidor de leads foi enviado = a 0 ou vazio
                if ($post_data['blockcreatelead'] == 0 || empty($post_data['blockcreatelead'])) {
                    if ($id == '') {
                        echo $this->createLead($post_data);
                        die;
                    }
                }
            }

            // verifica se foi o inibidor de update de leads foi enviado e é 1
            if (isset($post_data['blockupdatelead']) && $post_data['blockupdatelead'] == 1) {
                if ($id == '') {
                    echo $this->createLead($post_data);
                    die;
                } else {
                    //echo $this->returnError();
                    echo json_encode([
                        'success' => 'false',
                        'message' => 'o campo block update lead com o valor 1 inibe a criação do lead',
                        'code' => '200',
                        'id' => '',
                        'proposal_warning' => '',
                    ], JSON_UNESCAPED_UNICODE);
                    die;
                }
            } // verifica se foi o inibidor de update de leads foi enviado
            elseif (isset($post_data['blockupdatelead'])) {
                // verifica se foi o inibidor de leads foi enviado = a 0 ou vazio
                if ($post_data['blockupdatelead'] == 0 || empty($post_data['blockupdatelead'])) {
                    if ($id == '') {
                        echo $this->createLead($post_data);
                        die;
                    }
                }
            }


            // verifica se o id esta vazio
            if ($id == '') {
                // cria um novo lead
                echo $this->createLead($post_data);
                die;
            } // se não estiver atualiza
            else {
                //atualiza um novo lead
                echo $this->updateLead($post_data, $get_lead_by_email[0], $id);
                die;
            }

            die;
        } else {
            $this->output->set_header('HTTP/1.1 403 Forbidden');
            echo $this->show_404;
        }

    }
}
