<?php

function getTariffCEB($tariff = '110', $units = 0): array
{
    $amounts = [];

    if (!in_array($tariff, ['110A', '110', '120', '140'], true)) {
        return $amounts;
    }

    $domestic_rates = [
        ['units' => 25,    'rate' =>  3.16],
        ['units' => 25,    'rate' =>  4.38],
        ['units' => 25,    'rate' =>  4.74],
        ['units' => 25,    'rate' =>  5.45],
        ['units' => 100,   'rate' =>  6.15],
        ['units' => 50,    'rate' =>  7.02],
        ['units' => 50,    'rate' =>  7.90],
        ['units' => 200,   'rate' => 10.46],
        ['units' => 500,   'rate' => 10.68],
        ['units' => 500,   'rate' => 10.91],
        ['units' => 500,   'rate' => 11.13],
        ['units' => 99999, 'rate' => 11.36],
    ];

    $rates = [
        '110A' => $domestic_rates,
        '110'  => $domestic_rates,
        '120'  => $domestic_rates,
        '140'  => $domestic_rates,
    ];

    // 110A is different in the first 3 tariffs
    $rates['110A'][0] = ['units' => 25, 'rate' => 2.18];
    $rates['110A'][1] = ['units' => 25, 'rate' => 3.04];
    $rates['110A'][2] = ['units' => 25, 'rate' => 3.28];

    // Minimum tariff to pay
    $min_tariff = [
        '110A' => 31,
        '110'  => 44,
        '120'  => 184,
        '140'  => 369,
    ];

    $amounts['total'] = 0;
    foreach($rates[$tariff] as $part_rate) {
        if ($part_rate['units'] > $units) {
            $sub_total = $units * $part_rate['rate'];
            $amounts['part_calc'][] = [
                'units'  => $units,
                'rate'   => $part_rate['rate'],
                'amount' => number_format($sub_total, 2),
            ];
        }
        else {
            $units     -= $part_rate['units'];
            $sub_total = $part_rate['units'] * $part_rate['rate'];
            $amounts['part_calc'][] = [
                'units'  => $part_rate['units'],
                'rate'   => $part_rate['rate'],
                'amount' => number_format($sub_total, 2),
            ];
        }

        $amounts['total'] += $sub_total;
        if ($units <= $part_rate['units']) {
            break;
        }
    }

    // to pay
    $amounts['to_pay'] = $amounts['total'];
    if ($amounts['total'] < $min_tariff[$tariff]) {
        $amounts['to_pay'] = $min_tariff[$tariff];
    }

    // 2 decimal places...
    $amounts['total']  = number_format($amounts['total'], 2);
    $amounts['to_pay'] = number_format($amounts['to_pay'], 2);

    return $amounts;
}

try {
    $req    = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    $tariff = trim(strip_tags($req['tariff']));
    $units  = (int)$req['units'];

    try {
        $response = [
            'status' => 'OK',
            'data'   => getTariffCEB($tariff, $units),
        ];
    }
    catch (Exception $e) {
        $response = [
            'status' => 'ERR',
            'data'   => []
        ];
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
    exit(0);
}
catch (Exception $e) { }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
  <title>Calculate CEB Bill</title>
  <style>
      .copyleft {
          display:inline-block;
          -moz-transform: scale(-1, 1);
          -webkit-transform: scale(-1, 1);
          -o-transform: scale(-1, 1);
          -ms-transform: scale(-1, 1);
          transform: scale(-1, 1);
      }
  </style>
</head>
<body>
<div class="container font-monospace">
  <div class="row">
    <div class="col">
      <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
          <a class="navbar-brand" href="#">CEB Bill calculator</a>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
              <li class="nav-item">
                <a class="nav-link" aria-current="page" target="_blank" href="//github.com/nadimattari/">Github (Nadim Attari)</a>
              </li>
              <li class="nav-item"><a class="nav-link">|</a></li>
              <li class="nav-item">
                <a class="nav-link" aria-current="page" target="_blank" href="//github.com/MrSunshyne">Frontend/AlpineJS help from Sandeep</a>
              </li>
            </ul>
          </div>
        </div>
      </nav>
    </div>
  </div>
  <div class="row mt-5">
    <div class="col text-center">
      <img class="img-fluid rounded mx-auto d-block" src="CEB-bill.png" alt="CEB Bill" />
    </div>
  </div>
  <div class="row my-5" x-data="formData()">
    <div class="col-4 bg-info p-2">
      <form @submit.prevent="getTotal">
        <div data-mdb-input-init class="form-outline mb-4">
          <label class="form-label" for="tariff">Tariff</label>
          <select id="tariff" x-model="tariff" class="form-select" aria-label="Tariff">
            <template x-for="option in ['110A', '110', '120', '140']">
              <option :value="option" x-text="option"></option>
            </template>
          </select>
        </div>
        <div data-mdb-input-init class="form-outline mb-4">
          <label class="form-label" for="units">Units consumed</label>
          <input x-model="units" id="units" type="text" class="form-control" />
        </div>
        <button data-mdb-ripple-init type="submit" class="btn btn-primary btn-block float-end">C A L C U L A T E</button>
      </form>
    </div>
    <div class="col-8">
      <table class="table">
        <thead>
        <tr class="text-end">
          <th>Part units consumed</th>
          <th>Rate (Rs)</th>
          <th>Total (Rs)</th>
        </tr>
        </thead>
        <tfoot>
        <tr class="text-end">
          <th colspan="2">TOTAL</th>
          <th x-html="response.total"></th>
        </tr>
        </tfoot>
        <tbody>
          <template x-for="item in response.part_calc">
            <tr class="text-end">
              <td x-text="item.units"></td>
              <td x-text="item.rate"></td>
              <td x-text="item.amount"></td>
            </tr>
          </template>
        </tbody>
      </table>
      <p class="text-center fs-1 mt-3 fw-bolder font-monospace" x-show="response.to_pay != null" x-html="'TO PAY: ' + response.to_pay"></p>
    </div>
  </div>
  <footer class="text-center p-3 mb-2 bg-light text-muted"><span class="copyleft">&copy;</span> Nadim Attari | Use at your own riks</footer>
</div>
<script src="//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
<script src="//cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js" defer></script>
<script>
    function formData() {
      return {
        tariff    : '110',
        units     : 0,
        response  : {
            to_pay   : null,
            total    : null,
            part_calc: [],
        },
        getTotal () {
            fetch('<?php echo basename(__FILE__) ?>', {
                method        : 'POST',
                mode          : 'cors',
                cache         : 'no-cache',
                credentials   : 'same-origin',
                headers       : { 'Content-Type': 'application/json' },
                redirect      : 'follow',
                referrerPolicy: "no-referrer",
                body          : JSON.stringify({
                    tariff: this.tariff,
                    units : this.units,
                }),
            })
            .then(res => res.json())
            .then(res => this.response = res.data);
        },
      }
    };
</script>
</body>
</html>
