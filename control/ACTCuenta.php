<?php
/**
*@package pXP
*@file ACTCuenta.php
*@author  Gonzalo Sarmiento Sejas
*@date 21-02-2013 15:04:03
*@description Clase que recibe los parametros enviados por la vista para mandar a la capa de Modelo
*/
require_once(dirname(__FILE__).'/../reportes/RPlanCuentas.php');
require_once(dirname(__FILE__).'/../reportes/RBalanceGeneral.php');
require_once(dirname(__FILE__).'/../reportes/RResultados.php');
require_once(dirname(__FILE__).'/../../pxp/pxpReport/DataSource.php');
require_once(dirname(__FILE__).'/../reportes/RResultadosXls.php');

class ACTCuenta extends ACTbase{    
			
	function listarCuenta(){
		$this->objParam->defecto('ordenacion','id_cuenta');

		$this->objParam->defecto('dir_ordenacion','asc');
		
		if($this->objParam->getParametro('id_gestion')!=''){
            $this->objParam->addFiltro("cta.id_gestion = ".$this->objParam->getParametro('id_gestion'));    
        }
		
		if($this->objParam->getParametro('sw_control_efectivo')!=''){
            $this->objParam->addFiltro("cta.sw_control_efectivo = ''".$this->objParam->getParametro('sw_control_efectivo')."''");    
        }
		
		if($this->objParam->getParametro('id_partida')!=''){
            $this->objParam->addFiltro("cta.id_cuenta IN (select id_cuenta 
            							from conta.tcuenta_partida where id_partida = ".$this->objParam->getParametro('id_partida') . ") ");    
        }
        
        
        if($this->objParam->getParametro('sw_transaccional')!=''){
            $this->objParam->addFiltro("cta.sw_transaccional = ''".$this->objParam->getParametro('sw_transaccional')."''"); 
        }
        
		
		if($this->objParam->getParametro('tipoReporte')=='excel_grid' || $this->objParam->getParametro('tipoReporte')=='pdf_grid'){
			$this->objReporte = new Reporte($this->objParam,$this);
			$this->res = $this->objReporte->generarReporteListado('MODCuenta','listarCuenta');
		} else{
			$this->objFunc=$this->create('MODCuenta');
			
			$this->res=$this->objFunc->listarCuenta($this->objParam);
		}
		$this->res->imprimirRespuesta($this->res->generarJson());
	}
	
	function listarCuentaArb(){
        
        //obtiene el parametro nodo enviado por la vista
        $node=$this->objParam->getParametro('node');

        $id_cuenta=$this->objParam->getParametro('id_cuenta');
        $tipo_nodo=$this->objParam->getParametro('tipo_nodo');
        
                   
        if($node=='id'){
            $this->objParam->addParametro('id_padre','%');
        }
        else {
            $this->objParam->addParametro('id_padre',$id_cuenta);
        }
        
		$this->objFunc=$this->create('MODCuenta');
        $this->res=$this->objFunc->listarCuentaArb();
        
        $this->res->setTipoRespuestaArbol();
        
        $arreglo=array();
        
        array_push($arreglo,array('nombre'=>'id','valor'=>'id_cuenta'));
        array_push($arreglo,array('nombre'=>'id_p','valor'=>'id_cuenta_padre'));
        
        
        array_push($arreglo,array('nombre'=>'text','valores'=>'<b> #nro_cuenta# - #nombre_cuenta#</b>'));
        array_push($arreglo,array('nombre'=>'cls','valor'=>'nombre_cuenta'));
        array_push($arreglo,array('nombre'=>'qtip','valores'=>'<b> #nro_cuenta#</b><br/><b> #nombre_cuenta#</b><br> #desc_cuenta#'));
        
        
        $this->res->addNivelArbol('tipo_nodo','raiz',array('leaf'=>false,
                                                        'allowDelete'=>true,
                                                        'allowEdit'=>true,
                                                        'cls'=>'folder',
                                                        'tipo_nodo'=>'raiz',
                                                        'icon'=>'../../../lib/imagenes/a_form.png'),
                                                        $arreglo);
         
        /*se ande un nivel al arbol incluyendo con tido de nivel carpeta con su arreglo de equivalencias
          es importante que entre los resultados devueltos por la base exista la variable\
          tipo_dato que tenga el valor en texto = 'hoja' */
                                                                

         $this->res->addNivelArbol('tipo_nodo','hijo',array(
                                                        'leaf'=>false,
                                                        'allowDelete'=>true,
                                                        'allowEdit'=>true,
                                                        'tipo_nodo'=>'hijo',
                                                        'icon'=>'../../../lib/imagenes/a_form.png'),
                                                        $arreglo);
														
		
		$this->res->addNivelArbol('tipo_nodo','hoja',array(
                                                        'leaf'=>true,
                                                        'allowDelete'=>true,
                                                        'allowEdit'=>true,
                                                        'tipo_nodo'=>'hoja',
                                                        'icon'=>'../../../lib/imagenes/a_table_gear.png'),
                                                        $arreglo);												
														

        $this->res->imprimirRespuesta($this->res->generarJson());         

 }
				
	function insertarCuenta(){
		$this->objFunc=$this->create('MODCuenta');	
		if($this->objParam->insertar('id_cuenta')){
			$this->res=$this->objFunc->insertarCuenta($this->objParam);			
		} else{			
			$this->res=$this->objFunc->modificarCuenta($this->objParam);
		}
		$this->res->imprimirRespuesta($this->res->generarJson());
	}
						
	function eliminarCuenta(){
			$this->objFunc=$this->create('MODCuenta');	
		$this->res=$this->objFunc->eliminarCuenta($this->objParam);
		$this->res->imprimirRespuesta($this->res->generarJson());
	}
	
	function recuperarDatosPlanCuentas(){
    	
		$this->objFunc = $this->create('MODCuenta');
		$cbteHeader = $this->objFunc->listarPlanCuentas($this->objParam);
		if($cbteHeader->getTipo() == 'EXITO'){
				
			return $cbteHeader->getDatos();
		}
        else{
		    $cbteHeader->imprimirRespuesta($cbteHeader->generarJson());
			exit;
		}              
		
    }
	
	function reportePlanCuentas(){
			
		$nombreArchivo = uniqid(md5(session_id()).'PlanCuentas') . '.pdf'; 
		$dataSource = $this->recuperarDatosPlanCuentas();	
		
		
		//parametros basicos
		$tamano = 'LETTER';
		$orientacion = 'P';
		$titulo = 'Plan de Cuentas Gestón XXXX';
		
		$this->objParam->addParametro('orientacion',$orientacion);
		$this->objParam->addParametro('tamano',$tamano);		
		$this->objParam->addParametro('titulo_archivo',$titulo);	
        
		$this->objParam->addParametro('nombre_archivo',$nombreArchivo);
		//Instancia la clase de pdf
		
		$reporte = new RPlanCuentas($this->objParam);
		$reporte->datosHeader($dataSource);
		//$this->objReporteFormato->renderDatos($this->res2->datos);
		
		$reporte->generarReporte();
		$reporte->output($reporte->url_archivo,'F');
		
		$this->mensajeExito=new Mensaje();
		$this->mensajeExito->setMensaje('EXITO','Reporte.php','Reporte generado','Se generó con éxito el reporte: '.$nombreArchivo,'control');
		$this->mensajeExito->setArchivoGenerado($nombreArchivo);
		$this->mensajeExito->imprimirRespuesta($this->mensajeExito->generarJson());
		
	}
   
   function recuperarDatosBalanceGeneral(){
    	
		$this->objFunc = $this->create('MODCuenta');
		$cbteHeader = $this->objFunc->listarBalanceGeneral($this->objParam);
		if($cbteHeader->getTipo() == 'EXITO'){
				
			return $cbteHeader->getDatos();
		}
        else{
		    $cbteHeader->imprimirRespuesta($cbteHeader->generarJson());
			exit;
		}              
		
    }
   
   function reporteBalanceGeneral(){
			
		$nombreArchivo = uniqid(md5(session_id()).'PlanCuentas') . '.pdf'; 
		$dataSource = $this->recuperarDatosBalanceGeneral();	
		
		//parametros basicos
		$tamano = 'LETTER';
		$orientacion = 'P';
		$titulo = 'Balance General';
		
		$this->objParam->addParametro('orientacion',$orientacion);
		$this->objParam->addParametro('tamano',$tamano);		
		$this->objParam->addParametro('titulo_archivo',$titulo);        
		$this->objParam->addParametro('nombre_archivo',$nombreArchivo);
		//Instancia la clase de pdf
		
		$reporte = new RBalanceGeneral($this->objParam);
		$reporte->datosHeader($dataSource, $this->objParam->getParametro('nivel'), $this->objParam->getParametro('desde'),$this->objParam->getParametro('hasta'),  $this->objParam->getParametro('codigos'), $this->objParam->getParametro('tipo_balance'), $this->objParam->getParametro('incluir_cierre'));
		//$this->objReporteFormato->renderDatos($this->res2->datos);
		
		$reporte->generarReporte();
		$reporte->output($reporte->url_archivo,'F');
		
		$this->mensajeExito=new Mensaje();
		$this->mensajeExito->setMensaje('EXITO','Reporte.php','Reporte generado','Se generó con éxito el reporte: '.$nombreArchivo,'control');
		$this->mensajeExito->setArchivoGenerado($nombreArchivo);
		$this->mensajeExito->imprimirRespuesta($this->mensajeExito->generarJson());
		
	}
   function clonarCuentasGestion(){
		$this->objFunc=$this->create('MODCuenta');	
		$this->res=$this->objFunc->clonarCuentasGestion($this->objParam);
		$this->res->imprimirRespuesta($this->res->generarJson());
	}
   function recuperarDatosResultados(){
    	
		$this->objFunc = $this->create('MODCuenta');
		$cbteHeader = $this->objFunc->listarDetResultados($this->objParam);
		if($cbteHeader->getTipo() == 'EXITO'){
				
			return $cbteHeader->getDatos();
		}
        else{
		    $cbteHeader->imprimirRespuesta($cbteHeader->generarJson());
			exit;
		}              
		
    }
   
   function reporteResultados(){
		
		if($this->objParam->getParametro('formato') == 'pdf' ){
			$nombreArchivo = uniqid(md5(session_id()).'PlanCuentas') . '.pdf'; 
			$dataSource = $this->recuperarDatosResultados();	
			
			//parametros basicos
			$tamano = 'LETTER';
			$orientacion = 'P';
			$titulo = 'Estado de Resultados';
			
			$this->objParam->addParametro('orientacion',$orientacion);
			$this->objParam->addParametro('tamano',$tamano);		
			$this->objParam->addParametro('titulo_archivo',$titulo);        
			$this->objParam->addParametro('nombre_archivo',$nombreArchivo);
			//Instancia la clase de pdf
			
			$reporte = new RResultados($this->objParam);
			$reporte->datosHeader($dataSource, $this->objParam->getParametro('titulo_rep'), $this->objParam->getParametro('desde'),$this->objParam->getParametro('hasta'),  $this->objParam->getParametro('codigos'));
			//$this->objReporteFormato->renderDatos($this->res2->datos);
			
			$reporte->generarReporte();
			$reporte->output($reporte->url_archivo,'F');
			
			$this->mensajeExito=new Mensaje();
			$this->mensajeExito->setMensaje('EXITO','Reporte.php','Reporte generado','Se generó con éxito el reporte: '.$nombreArchivo,'control');
			$this->mensajeExito->setArchivoGenerado($nombreArchivo);
			$this->mensajeExito->imprimirRespuesta($this->mensajeExito->generarJson());
		}
		else{
			//genera reprote en excel ....
			$this->reporteResultadosXls();
		}	
		
		
	}

    
	
	function reporteResultadosXls()	{
		
		
		$dataSource = $this->recuperarDatosResultados();
		
		
		
		//TODO recueprar configuracion ....
		
		$config = 'carta_horizontal';
		$titulo = $this->objParam->getParametro('titulo_rep');
		$nombreArchivo=uniqid(md5(session_id()));
		
		//obtener tamaño y orientacion
		if ($config == 'carta_vertical') {
			$tamano = 'LETTER';
			$orientacion = 'P';
		} else if ($config == 'carta_horizontal') {
			$tamano = 'LETTER';
			$orientacion = 'L';
		} else if ($config == 'oficio_vertical') {
			$tamano = 'LEGAL';
			$orientacion = 'P';
		} else {
			$tamano = 'LEGAL';
			$orientacion = 'L';
		}
		
		$this->objParam->addParametro('orientacion',$orientacion);
		$this->objParam->addParametro('tamano',$tamano);		
		$this->objParam->addParametro('titulo_archivo',$titulo);
		$this->objParam->addParametro('test',$titulo);
		
		
		
			
		$nombreArchivo.='.xls';
		$this->objParam->addParametro('nombre_archivo',$nombreArchivo);
		//$this->objParam->addParametro('config',$this->res->datos[0]);
		$this->objParam->addParametro('datos',$dataSource);
		
		//Instancia la clase de excel
		$this->objReporteFormato=new RResultadosXls($this->objParam);
		if($this->objParam->getParametro('extendido') == 'si'){
			$this->objReporteFormato->imprimeDatosExtendido();
		}
		else{
			$this->objReporteFormato->imprimeDatos();
		}
		
		$this->objReporteFormato->generarReporte();		
		
		
		//Retorna nombre del archivo
		$this->mensajeExito=new Mensaje();
		$this->mensajeExito->setMensaje('EXITO','Reporte.php','Reporte generado','Se generó con éxito el reporte: '.$nombreArchivo,'control');
		$this->mensajeExito->setArchivoGenerado($nombreArchivo);
		$this->mensajeExito->imprimirRespuesta($this->mensajeExito->generarJson());
				
	}

   
			
}

?>