<?php
namespace SinticBolivia\MonoBusiness\Modules\Invoices;
use SinticBolivia\SBFramework\Classes\SB_Language;
use SinticBolivia\SBFramework\Classes\SB_Module;
use SinticBolivia\SBFramework\Classes\SB_Object;
use SinticBolivia\SBFramework\Classes\SB_Menu;
use SinticBolivia\SBFramework\Classes\SB_Route;
use SinticBolivia\SBFramework\Classes\SB_Request;
use SinticBolivia\SBFramework\Classes\SB_Factory;

define('MOD_INVOICES_DIR', dirname(__FILE__));
define('MOD_INVOICES_URL', MODULES_URL . '/' . basename(MOD_INVOICES_DIR));
define('MOD_INVOICES_TPL_DIR', MOD_INVOICES_DIR . SB_DS . 'tpl');

require_once MOD_INVOICES_DIR . SB_DS . 'classes' . SB_DS . 'interface.invoice.php';
require_once MOD_INVOICES_DIR . SB_DS . 'classes' . SB_DS . 'class.invoice.php';
require_once MOD_INVOICES_DIR . SB_DS . 'functions.php';

class LT_ModuleInvoices extends SB_Object
{
	public function __construct()
	{
		SB_Language::loadLanguage(LANGUAGE, 'invoices', MOD_INVOICES_DIR . SB_DS . 'locale');
		$this->AddActions();
	}
	protected function AddActions()
	{
		b_add_action('init', [$this, 'action_init']);
		if( lt_is_admin() )
		{
			SB_Module::add_action('admin_menu', array($this, 'action_admin_menu'));
			
			if( !SB_Module::moduleExists('mb') )
			{
				//SB_Module::add_action('settings_tabs', array($this, 'action_settings_tabs'));
				//SB_Module::add_action('settings_tabs_content', array($this, 'action_settings_tabs_content'));
				//SB_Module::add_action('save_settings', array($this, 'action_save_settings') );
			}
			if( SB_Module::moduleExists('quotes') )
			{
				SB_Module::add_action('quote_buttons', array($this, 'action_quote_buttons'));
			}
			SB_Module::add_action('lt_js_globals', array($this, 'lt_js_globals'));
			//##reports hooks
			SB_Module::add_action('mb_report_sales_tabs', array($this, 'action_mb_report_sales_tabs'));
			SB_Module::add_action('mb_report_sales_form_sales_book', array($this, 'action_mb_report_sales_form_sales_book'));
			SB_Module::add_action('mb_report_sales_build_sales_book', array($this, 'action_mb_report_sales_build_sales_book'));
			//b_add_action('init', [$this, 'action_admin_init']);
		}
		else
		{
			b_add_action('before_process_module', function($ctrl, $method)
			{
				if( !defined('API_REQUEST') )
					return false;
				SB_Factory::getApplication()->Log(sprintf("INVOICES API: [%s]\nAGENT: %s\nIP: %s\nGET: %s\nPOST: %s\n", 
					date('Y-m-d H:i:s'), 
					$_SERVER['HTTP_USER_AGENT'],
					$_SERVER['REMOTE_ADDR'],
					print_r($_GET, 1), 
					file_get_contents('php://input')));
			});
		}
		//##check if monobusiness is installed
		if( SB_Module::moduleExists('mb') )
		{
			require_once MOD_INVOICES_DIR . SB_DS . 'mono_business.php';
			$mb_hooks = new LT_ModuleInvoicesMonoBusiness();
		}
		b_add_action('rewrite_routes', function($routes)
		{
			require_once MOD_INVOICES_DIR . SB_DS . 'web-routes.php';
			require_once MOD_INVOICES_DIR . SB_DS . 'api-routes.php';
			return $routes;
		});
	}
	public function action_admin_menu()
	{
		SB_Menu::addMenuChild('menu-management', 
								'<span class="glyphicon glyphicon-qrcode"></span> ' . __('Invoices', 'invoices'), 
								SB_Route::_('index.php?mod=invoices'), 'menu-invoices', 
								'manage_invoices');
		if( !SB_Module::moduleExists('mb') || !SB_Module::IsEnabled('mb') )
		{
			SB_Menu::addMenuChild('menu-management', __('Invoices Settings', 'invoices'), 
								SB_Route::_('index.php?mod=invoices&view=settings'), 
								'menu-invoices-settings',
								'manage_invoices_settings');
		}
	}
	public function lt_js_globals($js)
	{
		$js['modules']['invoices'] = array(
				'locale' 				=> array(
						'CUSTOMER_NAME_NEEDED'	=> __('You need to enter a customer name', 'invoices'),
						'CUSTOMER_NIT_NEEDED'	=> __('You need to enter a customer nit/ruc/nif', 'invoices'),
						'CUSTOMER_INVALID_NIT'	=> __('The nit/ruc/nif is invalid', 'invoices')
				),
				'completion_url' 		=> SB_Route::_('index.php?mod=invoices&task=search_product'),
				'mod_customers_exists' 	=> SB_Module::moduleExists('customers')
		);
		return $js;
	}
	public function action_settings_tabs()
	{
		?>
		<li><a href="#billing"><?php _e('Billing', 'invoices'); ?></a></li>
		<?php 
	}
	public function action_settings_tabs_content()
	{
		$ops = (object)sb_get_parameter('invoices_ops', array());
		if( !defined('COUNTRY_CODE') )
		{
			printf("<h4>%s</h4>", __('Your country settings are incorrect.', 'invoices'));
			return false;
		}
		$query = "SELECT * FROM mb_invoice_dosages ORDER BY creation_date DESC";
		$this->dosages = SB_Factory::getDbh()->FetchResults($query);
		require_once 'views/admin/settings.php';
	}
	public function action_save_settings()
	{
		$ops = SB_Request::getVar('invoices_ops');
		if( !$ops || !is_array($ops))
			return false;
		sb_update_parameter('invoices_ops', $ops);
	}
	public function action_quote_buttons($quote)
	{
		if( !$quote )
			return false;
		?>
		<?php if( $quote->_invoice_id ): ?>
		<a href="<?php print SB_Route::_('index.php?mod=invoices&view=view&print=1&id='.$quote->_invoice_id); ?>" class="btn btn-warning btn-sm">
			<span class="glyphicon glyphicon-print"></span> <?php _e('Print Invoice', 'invoices'); ?>
		</a>
		<?php endif; ?>
		<?php 
	}
	
	public function action_mb_report_sales_tabs()
	{
		$tab = SB_Request::getString('tab');
		?>
		<li class="<?php print $tab == 'sales_book' ? 'active' : ''; ?>">
			<a href="<?php print SB_Route::_('index.php?mod=mb&view=reports.default&report=sales&tab=sales_book')?>">
				<?php _e('Libro de Ventas', 'invoices'); ?>
			</a>
		</li>
		<?php 
	}
	public function action_mb_report_sales_form_sales_book()
	{
		$from_date		= SB_Request::getDate('from_date', date('Y-m-d'));
		$to_date		= SB_Request::getDate('to_date', date('Y-m-d'));
		$store_id 		= SB_Request::getInt('store_id');
		$user 			= sb_get_current_user();
		if( !$user->can('report_sales_book') )
		{
			?>
			<h3 class="alert alert-danger"><b><?php _e('You dont have enough permissions to build sales book report', 'invoices'); ?></b></h3>
			<?php 
			return false;
		}
		$stores 		= SB_Warehouse::GetUserStores($user);
		?>
		<h2><?php _e('Libro de Ventas - IVA', 'invoices'); ?></h2>
		<form id="form-build" action="" method="get" class="hidden-print">
			<input type="hidden" name="mod" value="mb" />
			<input type="hidden" name="view" value="reports.default" />
			<input type="hidden" name="report" value="sales" />
			<input type="hidden" name="tab" value="sales_book" />
			<input type="hidden" name="build" value="1" />
			<div class="row form-group-sm">
				<div class="col-md-3">
					<div class="form-group">
						<label><?php _e('Store', 'mb'); ?></label>
						<select name="store_id" class="form-control">
							<option value="-1"><?php _e('-- todos --', 'invoices'); ?></option>
							<?php foreach($stores as $store): ?>
							<option value="<?php print $store->store_id; ?>" <?php print $store_id == $store->store_id ? 'selected' : ''; ?>>
								<?php print $store->store_name; ?>
							</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<div class="col-md-2">
					<div class="form-group">
						<label><?php _e('Fecha Desde', 'invoices'); ?></label>
						<input type="text" name="from_date" value="<?php print sb_format_date($from_date); ?>" placeholder="<?php _e('Fecha Desde', 'invoices'); ?>" class="form-control datepicker" />
					</div>
				</div>
				<div class="col-md-2">
					<div class="form-group">
						<label><?php _e('Fecha Hasta', 'invoices'); ?></label>
						<input type="text" name="to_date" value="<?php print sb_format_date($to_date); ?>" placeholder="<?php _e('Fecha Hasta', 'invoices'); ?>" class="form-control datepicker" />
					</div>
				</div>
				<div class="col-md-2">
					<div class="form-group">
						<label>&nbsp;</label><br/>
						<button class="btn btn-primary btn-sm"><?php _e('Build', 'mb');?></button>
					</div>
				</div>
			</div>
		</form>
		<?php 
	}
	public function action_mb_report_sales_build_sales_book()
	{
		if( !SB_Request::getInt('build') )
			return false;
		$store_id		= SB_Request::getInt('store_id');
		$from_date		= SB_Request::getDate('from_date', date('Y-m-d'));
		$to_date		= SB_Request::getDate('to_date', date('Y-m-d'));
		$export			= SB_Request::getString('export');
		$user = sb_get_current_user();
		if( !$user->can('report_sales_book') )
		{
			?>
			<h3 class="alert alert-danger"><b><?php _e('You dont have enough permissions to build sales book report', 'invoices'); ?></b></h3>
			<?php 
			return false;
		}
		$dbh 	= SB_Factory::getDbh();
		$stores = array();
		if( $store_id == -1 && $user->can('mb_see_all_stores') )
		{
			$stores = SB_Warehouse::GetUserStores($user);
		}
		elseif( $user->role_id !== 0 && !$user->_store_id )
		{
			$stores = array();
		}
		else 
		{
			$stores = array(new SB_MBStore($store_id));
		}
		$items 	= array();
		foreach($stores as $store)
		{
			$query = "SELECT * FROM mb_invoices 
						WHERE store_id = $store->store_id 
						AND DATE(invoice_date_time) >= '$from_date'
						AND DATE(invoice_date_time) <= '$to_date' 
						ORDER BY invoice_date_time ASC";
			$items[] = array(
					'store'	=> $store,
					'invoices'	=> $dbh->FetchResults($query)
			);
		}
		if( $export == 'excel' )
		{
			sb_include_lib('php-office/PHPExcel-1.8/PHPExcel.php');
			$font = new PHPExcel_Style_Font();
			$font->setBold(false);
			$font->setName('Arial');
			$font->setSize(8);
			$xls = new PHPExcel();
			$sheet = $xls->setActiveSheetIndex(0);
			$sheet->setCellValue("a1", 'Libro de Ventas - IVA');
			$sheet->setCellValue("a2", sprintf("Periodo Fiscal %s al %s", sb_format_date($from_date), sb_format_date($to_date)));
			$sheet->setCellValue("a3", SITE_TITLE);
			$row = 5;
			foreach($items as $item)
			{
				$sheet->setCellValue("a$row", $item['store']->store_name);
				$row++;
				$sheet->setCellValue("a$row", 'Especificacion');
				$sheet->setCellValue("b$row", 'Nro');
				$sheet->setCellValue("c$row", 'Fecha de la factura');
				$sheet->setCellValue("d$row", 'Nro. de la factura');
				$sheet->setCellValue("e$row", 'Nro. Autorizacion');
				$sheet->setCellValue("f$row", 'Estado');
				$sheet->setCellValue("g$row", 'NIT/CI Cliente');
				$sheet->setCellValue("h$row", 'Nombre o Razon Social');
				$sheet->setCellValue("i$row", 'Importe total de la venta');
				$sheet->setCellValue("j$row", 'Importe ICE/IEHD/TASAS');
				$sheet->setCellValue("k$row", 'Exportaciones y Operaciones Excentas');
				$sheet->setCellValue("l$row", 'Ventas Grabadas a Tasa Cero');
				$sheet->setCellValue("m$row", 'Subtotal');
				$sheet->setCellValue("n$row", 'Importe Base para Debito Fiscal');
				$sheet->setCellValue("o$row", 'Debito Fiscal');
				$sheet->setCellValue("p$row", 'Codigo de Control');
				
				$sheet->getStyle("a$row:p$row")->applyFromArray(
						array(
								'fill' => array(
										'type' => PHPExcel_Style_Fill::FILL_SOLID,
										'color' => array('rgb' => '1E90FF')
								),
								'font'  => array(
										'bold'  => true,
										'color' => array('rgb' => 'FFFFFF'),
										'size'  => 8,
										//'name'  => 'Verdana'
								),
								'alignment' => array(
										'horizontal'	=> 'center'
								)
						)
				);
				$row++;
				$i = 1;
				foreach($item['invoices'] as $inv)
				{
					$sheet->setCellValue("a$row", 0);
					$sheet->setCellValue("b$row", $i);
					$sheet->setCellValue("c$row", sb_format_date($inv->invoice_date_time));
					$sheet->setCellValue("d$row", $inv->invoice_number);
					$sheet->setCellValue("e$row", sprintf(" %s ", $inv->authorization));
					$sheet->setCellValue("f$row", $inv->status == 'void' ? 'A' : 'V');
					$sheet->setCellValue("g$row", $inv->nit_ruc_nif);
					$sheet->setCellValue("h$row", mb_invoice_get_meta($inv->invoice_id, '_billing_name'));
					$sheet->setCellValue("i$row", $inv->total);
					$sheet->setCellValue("j$row", 0);//importe ICE
					$sheet->setCellValue("k$row", 0); //Exportaciones Imp Tasa Cero
					$sheet->setCellValue("l$row", 0); //Ventas Grabadas
					$sheet->setCellValue("m$row", $inv->total);
					$sheet->setCellValue("n$row", $inv->total);
					$sheet->setCellValue("o$row", $inv->total * 0.13);
					$sheet->setCellValue("p$row", $inv->control_code);
					$row++;
					$i++;
				}
			}
			$sheet->getStyle("a1:p$row")->applyFromArray(
					array(
							'font'  => array(
									//'bold'  => !true,
									'color' => array('rgb' => '000000'),
									'size'  => 8,
									'name'  => 'Verdana'
							)
					)
			);
			$writer = PHPExcel_IOFactory::createWriter($xls, 'Excel2007');
			$xls_file = TEMP_DIR . SB_DS . 'libro-ventas.xlsx';
			$writer->save($xls_file);
			ob_clean();
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="libro-ventas.xlsx"');
			header('Cache-Control: max-age=0');
			readfile($xls_file);
			unlink($xls_file);
			die();
		}
		elseif( $export == 'pdf' )
		{
			$pdf = mb_invoices_get_pdf_instance('libro de ventas - IVA');
			
		}
		$buffer = ob_get_clean();
		ob_start();
		?>
		<?php if( !SB_Request::getInt('print') ): ?>
		<p>
			<a href="<?php print SB_Route::_('index.php?'.$_SERVER['QUERY_STRING'] . '&export=pdf'); ?>" class="btn btn-warning btn-sm" target="_blank">
				<span class="glyphicon glyphicon-print"></span> <?php _e('Imprimir', 'invoices'); ?></a>
			<a href="<?php print SB_Route::_('index.php?'.$_SERVER['QUERY_STRING'] . '&export=excel'); ?>" class="btn btn-primary btn-sm">
				<span class="glyphicon glyphicon-excel"></span> <?php _e('Exportar a Excel', 'invoices'); ?></a>
		</p>
		<?php endif; ?>
		<div class="table-responsive">
			<?php foreach($items as $item): ?>
			<h3><?php print $item['store']->store_name; ?></h3>
			<table class="table table-bordered table-hover table-condensed">
			<thead>
			<tr>
				<th>Nro.</th>
				<th>Fecha de la factura</th>
				<th>Nro. de la factura</th>
				<th>Nro. Autorizacion</th>
				<th>Estado</th>
				<th>NIT/CI Cliente</th>
				<th>Nombre o Razon Social</th>
				<th>Importe total de la venta</th>
				<th>Importe ICE/IEHD/TASAS</th>
				<th>Subtotal</th>
				<th>Importe Base para Debito Fiscal</th>
				<th>Debito Fiscal</th>
				<th>Codigo de Control</th>
			</tr>
			</thead>
			<tbody>
			<?php $i = 1; foreach($item['invoices'] as $inv): ?>
			<tr>
				<td><?php print $i; ?></td>
				<td><?php print sb_format_date($inv->invoice_date_time); ?></td>
				<td><?php print $inv->invoice_number; ?></td>
				<td><?php print $inv->authorization; ?></td>
				<td>
					<?php print $inv->status == 'void' ? 'A' : 'V'; ?>
				</td>
				<td><?php print $inv->nit_ruc_nif; ?></td>
				<td><?php print $inv->customer; ?></td>
				<td><?php print number_format($inv->total, 2, '.', ','); ?></td>
				<td><?php print 0; ?></td>
				<td><?php print number_format($inv->total, 2, '.', ','); ?></td>
				<td><?php print number_format($inv->total, 2, '.', ','); ?></td>
				<td><?php print number_format($inv->total * 0.13, 2, '.', ','); ?></td>
				<td><?php print $inv->control_code; ?></td>
			</tr>
			<?php $i++; endforeach; ?>
			</tbody>
			</table>
			<?php endforeach; ?>
		</div>
		<?php 
		$report = ob_get_clean();
		if( SB_Request::getInt('print') )
		{
			set_time_limit(0);
			ini_set('memory_limit', '264M');
			$pdf = mb_invoices_get_pdf_instance('Libro de Ventas - IVA');
			//$pdf->setPaper('A4', 'landscape');
			$pdf->loadHtml($report);
			$pdf->render();
			$pdf->stream('libro-de-ventas.pdf', array('Attachment' => 0));
			die();
		}
		print $buffer . $report;
	}
	public function action_init()
	{
		require_once MOD_INVOICES_DIR . SB_DS . 'siat_hooks.php';
		\SiatHooks::init();
	}
} 
new LT_ModuleInvoices();