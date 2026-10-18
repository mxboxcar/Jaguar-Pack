{{-- _admin/_report/current_cash.php เดิม --}}

<html>
<head>
  <title>balance</title>
  <meta name="GENERATOR" content="HeidiSQL 9.4.0.5186">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <style type="text/css">
    thead tr {background-color: ActiveCaption; color: CaptionText;}
    th, td {vertical-align: top; font-family: "Tahoma", Arial, Helvetica, sans-serif; font-size: 8pt; padding: 3px; }
    table, td {border: 1px solid silver;}
    table {border-collapse: collapse;}
    thead .col0 {width: 138px;}
    thead .col1 {width: 56px;}
    .col1 {text-align: center;}
  </style>
</head>

<body>

  <table caption="balance (1 rows)">
    <thead>
      <tr>
        <th class="col0">Date</th>
        <th class="col1">Amount</th>
      </tr>
    </thead>
    <tbody>

@foreach ($rows as $r)<tr><td class='col0'>{{ $r->code }}</td><td class='col1'>{{ \App\Domain\Reports\Rows::s($r->count) }}</td></tr>@endforeach



      
</tbody>
  </table>

  <p>
    <em>generated {{ date('Y-m-d H:i:s') }}</em>
  </p>

</body>
</html>
