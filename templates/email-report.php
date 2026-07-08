<?php
/**
 * Email Report Template
 * Called by the plugin's handle_submission() method.
 * Returns a fully-formed HTML email string.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function hvac_render_email( $p ) {

    $property  = esc_html( $p['ji_property'] ?? '' );
    $date      = esc_html( $p['ji_date']     ?? '' );
    $tech      = esc_html( $p['ji_tech']     ?? '' );
    $wo        = esc_html( $p['ji_wo']       ?? '' );
    $contract  = esc_html( $p['ji_contract'] ?? '' );
    $visit     = esc_html( $p['ji_visit']    ?? '' );
    $units     = intval( $p['unit_count']    ?? 0 );
    $submitted = date( 'F j, Y \a\t g:i A' );

    $units_data = is_array( $p['units']   ?? null ) ? $p['units']   : array();
    $signoff    = is_array( $p['signoff'] ?? null ) ? $p['signoff'] : array();

    $service_items = array(
        'Evaporator coil — light inspect & surface clean',
        'Flush and treat condensate drain lines',
        'Inspect control systems and safety devices',
        'Check contactors and electrical components',
        'Replace all air filters (included)',
        'Inspect condenser fan blades and motors',
        'Check refrigerant levels and system condition',
        'Inspect for visible leaks / abnormal condensation',
        'Evaluate overall system performance',
        'Service report provided after visit',
    );

    $ok_count = $mon_count = $action_count = $checked_items = $total_items = 0;
    foreach ( $units_data as $unit ) {
        $st = $unit['status'] ?? 'none';
        if ( $st === 'ok' )     $ok_count++;
        if ( $st === 'mon' )    $mon_count++;
        if ( $st === 'action' ) $action_count++;
        $checks = $unit['checks'] ?? array();
        $total_items   += count( $checks );
        $checked_items += count( array_filter( $checks ) );
    }
    $completion_pct = $total_items > 0 ? round( ( $checked_items / $total_items ) * 100 ) : 0;

    $status_map = array(
        'ok'     => array( '✓ OK',              '#D1FAE5', '#065F46' ),
        'mon'    => array( '⚠ Monitor',          '#FEF3C7', '#92400E' ),
        'action' => array( '● Action Required',  '#FEE2E2', '#991B1B' ),
        'none'   => array( '— Not Set',          '#F2F4F7', '#6B7280' ),
    );

    ob_start(); ?>
<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#F1F5F9;font-family:Arial,Helvetica,sans-serif;color:#1F2937;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#F1F5F9;padding:20px 0;">
<tr><td align="center">
<table width="620" cellpadding="0" cellspacing="0" style="max-width:620px;width:100%;">

  <!-- HEADER -->
  <tr><td style="background:#001C32;border-radius:10px 10px 0 0;border-bottom:3px solid #E07820;padding:16px 20px;">
    <table width="100%" cellpadding="0" cellspacing="0"><tr>
      <td><div style="font-size:18px;font-weight:800;color:#fff;letter-spacing:.5px;">EXPRESS AC &amp; REFRIGERATION</div>
          <div style="font-size:11px;color:#9FBDDA;margin-top:3px;">Service Checklist Report &mdash; <?php echo $units; ?>-Unit Split System</div></td>
      <td align="right"><div style="font-size:14px;font-weight:700;color:#fff;">(786) 322-0638</div>
                        <div style="font-size:10px;color:#9FBDDA;">expressacmiami.com</div></td>
    </tr></table>
  </td></tr>

  <!-- PROGRESS STRIP -->
  <tr><td style="background:#5D90C0;padding:10px 20px;">
    <table width="100%" cellpadding="0" cellspacing="0"><tr>
      <td style="color:#fff;font-size:12px;font-weight:600;">
        Completion: <?php echo $completion_pct; ?>% &nbsp;(<?php echo $checked_items; ?>/<?php echo $total_items; ?> items checked)
      </td>
      <td align="right">
        <span style="background:#E07820;color:#fff;font-size:11px;font-weight:700;padding:3px 10px;border-radius:99px;">
          Submitted <?php echo esc_html( $submitted ); ?>
        </span>
      </td>
    </tr></table>
  </td></tr>

  <!-- JOB INFO -->
  <tr><td style="background:#fff;padding:0;">
    <div style="background:#001C32;padding:7px 20px;font-size:12px;font-weight:700;color:#fff;text-transform:uppercase;letter-spacing:1px;">Job Information</div>
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
      <?php
      $ji = array(
          array('Property / Address', $property),
          array('Date of Service',    $date),
          array('Technician',         $tech),
          array('Work Order #',       $wo       ?: '—'),
          array('Contract #',         $contract ?: '—'),
          array('Visit Type',         $visit    ?: '—'),
      );
      foreach ( $ji as $i => $row ) :
          $bg = $i % 2 === 0 ? '#F2F4F7' : '#fff';
      ?>
      <tr>
        <td style="background:#F2F4F7;padding:7px 20px;font-size:12px;font-weight:700;border-bottom:1px solid #D1D5DB;width:36%;"><?php echo esc_html($row[0]); ?></td>
        <td style="background:<?php echo $bg; ?>;padding:7px 20px;font-size:12px;border-bottom:1px solid #D1D5DB;"><?php echo $row[1] ?: '<span style="color:#9FBDDA">—</span>'; ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </td></tr>

  <!-- SUMMARY -->
  <tr><td style="background:#fff;padding:12px 20px 8px;">
    <div style="background:#001C32;padding:7px 20px;font-size:12px;font-weight:700;color:#fff;text-transform:uppercase;letter-spacing:1px;margin-bottom:10px;">Summary</div>
    <table width="100%" cellpadding="0" cellspacing="0"><tr>
      <td align="center" style="background:#D1FAE5;padding:10px 6px;border-radius:6px;border:1px solid #BBFBDE;">
        <div style="font-size:24px;font-weight:800;color:#065F46;"><?php echo $ok_count; ?></div>
        <div style="font-size:10px;font-weight:700;color:#065F46;">✓ OK</div>
      </td>
      <td width="8"></td>
      <td align="center" style="background:#FEF3C7;padding:10px 6px;border-radius:6px;border:1px solid #FDE68A;">
        <div style="font-size:24px;font-weight:800;color:#92400E;"><?php echo $mon_count; ?></div>
        <div style="font-size:10px;font-weight:700;color:#92400E;">⚠ Monitor</div>
      </td>
      <td width="8"></td>
      <td align="center" style="background:#FEE2E2;padding:10px 6px;border-radius:6px;border:1px solid #FECACA;">
        <div style="font-size:24px;font-weight:800;color:#991B1B;"><?php echo $action_count; ?></div>
        <div style="font-size:10px;font-weight:700;color:#991B1B;">● Action Req.</div>
      </td>
      <td width="8"></td>
      <td align="center" style="background:#F2F4F7;padding:10px 6px;border-radius:6px;border:1px solid #D1D5DB;">
        <div style="font-size:24px;font-weight:800;color:#001C32;"><?php echo $units; ?></div>
        <div style="font-size:10px;font-weight:700;color:#6B7280;">Total Units</div>
      </td>
    </tr></table>
  </td></tr>

  <!-- UNIT DETAIL -->
  <tr><td style="background:#fff;padding:8px 20px 4px;">
    <div style="background:#001C32;padding:7px 20px;font-size:12px;font-weight:700;color:#fff;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">Unit Details</div>
    <?php foreach ( $units_data as $idx => $unit ) :
      $u_num    = intval( $unit['num']    ?? ($idx+1) );
      $u_status = $unit['status'] ?? 'none';
      $u_notes  = esc_html( $unit['notes'] ?? '' );
      $u_init   = esc_html( $unit['init']  ?? '' );
      $u_sup    = esc_html( $unit['sup']   ?? '' );
      $u_ret    = esc_html( $unit['ret']   ?? '' );
      $u_dt     = esc_html( $unit['dt']    ?? '' );
      $u_fs     = esc_html( $unit['fs']    ?? '' );
      $u_checks = $unit['checks'] ?? array();
      $u_done   = count( array_filter($u_checks) );
      $u_total  = count( $u_checks );

      $st       = $status_map[$u_status] ?? $status_map['none'];
      $is_flag  = in_array($u_status, array('mon','action'));
      $row_bg   = $is_flag ? $st[0] : ($idx%2===0?'#fff':'#F2F4F7');

      $unchecked = array();
      foreach ( $u_checks as $ci => $chk ) {
          if ( !$chk && isset($service_items[$ci]) ) $unchecked[] = $service_items[$ci];
      }
    ?>
    <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #D1D5DB;border-radius:6px;margin-bottom:5px;overflow:hidden;border-collapse:separate;">
      <tr style="background:#5D90C0;">
        <td style="padding:6px 12px;font-size:12px;font-weight:700;color:#fff;">UNIT <?php echo $u_num; ?></td>
        <td align="center" style="width:65px;padding:6px;">
          <span style="background:rgba(255,255,255,0.2);color:#fff;font-size:9px;font-weight:700;padding:2px 7px;border-radius:99px;"><?php echo $u_done; ?>/<?php echo $u_total; ?></span>
        </td>
        <td align="right" style="padding:6px 12px;width:150px;">
          <span style="background:<?php echo $st[1]; ?>;color:<?php echo $st[2]; ?>;font-size:9px;font-weight:700;padding:3px 8px;border-radius:99px;"><?php echo $st[0]; ?></span>
        </td>
      </tr>
      <?php if ( $is_flag || $u_notes || $u_sup || $u_ret || $u_dt || $u_fs ) : ?>
      <tr><td colspan="3" style="padding:7px 12px;background:<?php echo $is_flag ? $st[1] : '#F2F4F7'; ?>;font-size:11px;">
        <?php if ( $u_sup || $u_ret || $u_dt || $u_fs ) : ?>
        <div>
          <b style="color:#6B7280;">Supply:</b> <?php echo $u_sup ?: '—'; ?>°F &nbsp;
          <b style="color:#6B7280;">Return:</b> <?php echo $u_ret ?: '—'; ?>°F &nbsp;
          <b style="color:#6B7280;">ΔT:</b> <?php echo $u_dt ?: '—'; ?> &nbsp;
          <b style="color:#6B7280;">Filter:</b> <?php echo $u_fs ?: '—'; ?>
          <?php if ($u_init) : ?> &nbsp;<b style="color:#6B7280;">Initials:</b> <b style="color:#5D90C0;"><?php echo $u_init; ?></b><?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if ( $u_notes ) : ?>
        <div style="margin-top:4px;"><b style="color:#6B7280;">Notes:</b> <?php echo $u_notes; ?></div>
        <?php endif; ?>
        <?php if ( $is_flag && $unchecked ) : ?>
        <div style="margin-top:4px;color:#991B1B;"><b>Incomplete:</b> <?php echo esc_html(implode(', ', $unchecked)); ?></div>
        <?php endif; ?>
      </td></tr>
      <?php endif; ?>
    </table>
    <?php endforeach; ?>
  </td></tr>

  <!-- SIGN-OFF -->
  <?php if ( $signoff ) : ?>
  <tr><td style="background:#fff;padding:8px 20px 4px;">
    <div style="background:#001C32;padding:7px 20px;font-size:12px;font-weight:700;color:#fff;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">Sign-Off</div>
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
      <?php foreach ($signoff as $item) :
        $chk = !empty($item['checked']);
        $lbl = esc_html($item['label'] ?? '');
        $bg  = $chk ? '#D1FAE5' : '#F2F4F7';
        $col = $chk ? '#065F46' : '#6B7280';
        $ico = $chk ? '✓' : '☐';
      ?>
      <tr style="background:<?php echo $bg; ?>;border-bottom:1px solid #D1D5DB;">
        <td style="padding:6px 14px;font-size:12px;color:<?php echo $col; ?>;font-weight:<?php echo $chk?'700':'400'; ?>;"><?php echo $ico; ?> <?php echo $lbl; ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </td></tr>
  <?php endif; ?>

  <!-- FOOTER -->
  <tr><td style="background:#001C32;padding:14px 20px;border-radius:0 0 10px 10px;border-top:3px solid #E07820;text-align:center;">
    <div style="font-size:11px;color:#9FBDDA;">Express AC &amp; Refrigeration &nbsp;|&nbsp; (786) 322-0638 &nbsp;|&nbsp; expressacmiami.com &nbsp;|&nbsp; Licensed &amp; Insured</div>
    <div style="font-size:10px;color:#5D90C0;margin-top:4px;">Auto-generated by the Express AC Service Checklist system.</div>
  </td></tr>

</table>
</td></tr></table>
</body></html>
    <?php
    return ob_get_clean();
}
