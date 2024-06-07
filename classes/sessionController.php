<?php

// esta clase maneja de la misma manera las sessiones pero con la autenticacion de usuarios tambien
class SessionController extends Controller
{

    // creamos nuestros atributos
    private $userSession;
    private $userCorreo;
    private $userid;
    private $defaultSites;

    private $session;
    private $sites;

    private $user;

    function __construct()
    {
        parent::__construct();

        // cuando se cree un nuevo objeto de sessionController va llamar a init
        $this->init();
    }

    public function getUserSession()
    {
        return $this->userSession;
    }

    public function getUserCorreo()
    {
        return $this->userCorreo;
    }

    public function getUserId()
    {
        return $this->userid;
    }

    // Esta funcion la vamos a implementar para leer el JSON y asi dar los permisos
    private function init()
    {
        //se crea nueva sesión
        $this->session = new Session();
        //se carga el archivo json con la configuración de acceso
        $json = $this->getJSONFileConfig();
        // se asignan los sitios
        $this->sites = $json['sites'];
        // se asignan los sitios por default, los que cualquier rol tiene acceso
        $this->defaultSites = $json['default-sites'];
        // inicia el flujo de validación para determinar
        // el tipo de rol y permismos
        $this->validateSession();
    }
    // esta funcion nos permite abrir el archivo y devolver la data decodificada
    private function getJSONFileConfig()
    {
        // asignamos el contenido de access a string
        $string = file_get_contents("config/access.json");
        // decoficamos el JSON en un array de objetos
        $json = json_decode($string, true);

        return $json;
    }

    /**
     * Implementa el flujo de autorización
     * para entrar a las páginas
     */

    // funcion para implementar el fluo de autorizacion para entrar a las paginas
    function validateSession()
    {
        error_log('SessionController::validateSession()');
        //Si existe la sesión
        if ($this->existsSession()) {
            // obtenemos el rol para los permisos, con todo el usuario.
            $role = $this->getUserSessionData()->getIdRol();

            error_log("sessionController::validateSession(): username:" . $this->user->getCorreo() . " - role: " . $this->user->getIdRol());
            // validamos si a la pagina a entrar es publica o privada
            if ($this->isPublic()) {
                // si la pagina es publica entonces que lo rediriga a la pagina principal de cada rol
                $this->redirectDefaultSiteByRole($role);
                error_log("SessionController::validateSession() => sitio público, redirige al main de cada rol");
            } else {
                // si no es public hacemos la validacion de usuario para el login
                if ($this->isAuthorized($role)) {
                    error_log("SessionController::validateSession() => autorizado, lo deja pasar");
                    //si el usuario está en una página de acuerdo
                    // a sus permisos termina el flujo
                } else {
                    error_log("SessionController::validateSession() => no autorizado, redirige al main de cada rol");
                    // si el usuario no tiene permiso para estar en
                    // esa página lo redirije a la página de inicio
                    $this->redirectDefaultSiteByRole($role);
                }
            }
        } else {
            //No existe ninguna sesión
            //se valida si el acceso es público o no
            if ($this->isPublic()) {
                error_log('SessionController::validateSession() public page');
                //la pagina es publica
                //no pasa nada
            } else {
                //la página no es pública
                //redirect al index de mi pagina 
                error_log('SessionController::validateSession() redirect al login');
                header('location: ' . constant('URL') . '');
            }
        }
    }
    /**
     * Valida si existe sesión, 
     * si es verdadero regresa el usuario actual
     */
    function existsSession()
    {   
        // validamos que exista una session abierta
        if (!$this->session->exists()) return false;
        // si tambien la data de la session del usuario esta vacia retornamos false
        if ($this->session->getCurrentUser() == NULL) return false;

        // si no entra en los dos condicionales anteriores, eso significa que existe una session        
        $userid = $this->session->getCurrentUser();


        // si existe retorna true
        if ($userid) return true;

        return false;
    }
    
    // esta funcion nos servira para crear un nuevo modelo del usuario dependiendo de los datos de la session
    function getUserSessionData()
    {
        $id = $this->session->getCurrentUser();
        $this->user = new UserModel();

        // obtenemos la data del usuario que tiene session en user apartir del id
        $this->user->get($id);
        error_log("sessionController::getUserSessionData(): " . $this->user->getNombres());
        // finalmente retornamos toda la data del usuario
        return $this->user;
    }


    // la funcion initialize nos va permitir llamar authorizeAccess y establecer el
    public function initialize($user)
    {
        error_log("sessionController::initialize(): user: " . $user->getNombres());
        $this->session->setCurrentUser($user->getDocumento());
        $this->authorizeAccess($user->getRol());
    }


    
    // funcion para validar si una pagina es publica o no
    private function isPublic()
    {
        $currentURL = $this->getCurrentPage();
        error_log("sessionController::isPublic(): currentURL => " . $currentURL);
        // utilizamos una expresion regular para remplazar todos los caracteres que especificamos 
        // por un string vacio y va ser aplicado de currentURL
        $currentURL = preg_replace("/\?.*/", "", $currentURL); //omitir get info
        // utilizamos el for para recorrer el arreglo de objetos de sites y comparar si es publico o no
        for ($i = 0; $i < sizeof($this->sites); $i++) {
            if ($currentURL === $this->sites[$i]['site'] && $this->sites[$i]['access'] === 'public') {
                return true;
            }
        }
        return false;
    }

    // utilizamos esta funcion para redirigir al usuario a su respectivo sitio por rol
    private function redirectDefaultSiteByRole($role)
    {
        $url = '';
        for ($i = 0; $i < sizeof($this->sites); $i++) {

            // dependiendo del rol, lo redirijimos a una pagina u otra
            if ($this->sites[$i]['role'] === $role) {
                $url = '/manage/' . $this->sites[$i]['site'];
                break;
            } 
        }
        // redirigimos finalmente con url mapeada
        header('location: ' . $url);
    }

    // esta funcion nos sirve para validar, si ese usuario esta autorizado para entrar y ver esa pagina
    private function isAuthorized($role)
    {
        $currentURL = $this->getCurrentPage();
        $currentURL = preg_replace("/\?.*/", "", $currentURL); //omitir get info

        for ($i = 0; $i < sizeof($this->sites); $i++) {
            // aqui verificamos el rol y el sitio, entonces si, si tiene acceso retornamos true
            if ($currentURL === $this->sites[$i]['site'] && $this->sites[$i]['role'] === $role) {
                return true;
            }
        }
        return false;
    }

    // esta funcion nos va permitir obtener la pagina actual
    private function getCurrentPage()
    {
        // obtenemos la url actual
        $actual_link = trim("$_SERVER[REQUEST_URI]");
        // separamos la url por diagonales, retornandonos un arreglo con los elementos de la URL
        $url = explode('/', $actual_link);
        error_log("sessionController::getCurrentPage(): actualLink =>" . $actual_link . ", url => " . $url[2]);
        // despues del http
        return $url[2];
    }

    // esta funcion nos permite redigir al usuario haci su sitio principal
    function authorizeAccess($role)
    {
        error_log("sessionController::authorizeAccess(): role: $role");
        switch ($role) {
            case 'Administrador':
                $this->redirect($this->defaultSites['admin'], []);
                break;
            case 'mesero':
                $this->redirect($this->defaultSites['mesero'], []);
                break;
            default:
        }
    }


    // funcion para cerrar la session
    function logout()
    {
        $this->session->closeSession();
    }
}
