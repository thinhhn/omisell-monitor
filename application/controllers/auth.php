<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->config('supervisor');
        $this->load->library('session');
        $this->load->helper('url');
    }

    /**
     * Display login form
     */
    public function index()
    {
        // If already logged in, redirect to dashboard
        if ($this->isLoggedIn()) {
            redirect('welcome');
        }
        
        $data['error'] = $this->session->flashdata('error');
        $this->load->view('auth/login', $data);
    }

    /**
     * Process login form
     */
    public function login()
    {
        $username = $this->input->post('username');
        $password = $this->input->post('password');
        
        if (empty($username) || empty($password)) {
            $this->session->set_flashdata(
                'error', 
                'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu'
            );
            redirect('auth');
        }

        $accounts = $this->config->item('login_accounts');
        
        if (isset($accounts[$username]) && $accounts[$username] === $password) {
            // Login successful
            $session_data = [
                'logged_in' => true,
                'username' => $username,
                'login_time' => time()
            ];
            $this->session->set_userdata($session_data);
            
            $redirect = $this->config->item('redirect_after_login');
            redirect($redirect ? $redirect : 'welcome');
        } else {
            // Login failed
            $this->session->set_flashdata(
                'error', 
                'Tên đăng nhập hoặc mật khẩu không đúng'
            );
            redirect('auth');
        }
    }

    /**
     * Logout user
     */
    public function logout()
    {
        // Destroy entire session
        $this->session->sess_destroy();
        
        // Set success message in new session
        $this->session->set_flashdata('message', 'Đăng xuất thành công');
        
        // Redirect to login page
        redirect(base_url() . 'auth');
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn()
    {
        $logged_in = $this->session->userdata('logged_in');
        $login_time = $this->session->userdata('login_time');
        $timeout = $this->config->item('login_timeout');
        
        if (!$logged_in || !$login_time) {
            return false;
        }
        
        // Check session timeout
        if ((time() - $login_time) > $timeout) {
            $this->logout();
            return false;
        }
        
        return true;
    }

    /**
     * AJAX check login status
     */
    public function checkSession()
    {
        header('Content-Type: application/json');
        echo json_encode(['logged_in' => $this->isLoggedIn()]);
    }
}

/* End of file auth.php */
/* Location: ./application/controllers/auth.php */