@extends('admin.layout')

@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    Fraud Section
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#"><i class="fa fa-cog mr-2"></i>Admin Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Fraud</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include('admin.fraud.partials.topmenu')
    <div class="row">
        <div class="col-xl-4 col-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">High Depositors</h3>
                </div>
                <div class="card-body table-responsive">
                    <p>List of transactions made by customers depositing over 2k EUR / 3k AUD / 3k CAD / 1.5k GBP / 19 k
                        NOK /
                        19k SEK / 2k USD / 15k DKK or more within a 24-hour period (00:00 – 23:59).</p>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Non Turned-over Withdrawals</h3>
                </div>
                <div class="card-body table-responsive">
                    <p>List of customers depositing and requesting withdrawals, having wagered less than 100%.
                        Total amount of deposits from second last withdrawal to last withdrawal versus wagering.</p>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Anonymous Method Deposits</h3>
                </div>
                <div class="card-body table-responsive">
                    <p>List of transactions made by customers depositing with fully anonymous methods (currently
                        Paysafe)
                        within 24hours (00:00 – 23:59)</p>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xl-4 col-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Multi-Method Transactions</h3>
                </div>
                <div class="card-body table-responsive">
                    <p>List of transactions made by customers who depositing & withdrawing with 2 or more different
                        methods within
                        24hours (00:00 – 23:59). Pre-paid cards included separately per instance (one-off use).</p>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Big Winners and Big Losers</h3>
                </div>
                <div class="card-body table-responsive">
                    <p>List of customers within 24 hours (00:00 – 23:59) that have won or lost more than a threshold
                        (3000 € by default). Can be filtered by date and threshold amount. </p>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Battle of slots Gladiators</h3>
                </div>
                <div class="card-body table-responsive">
                    <p>List of customers participating in a Battle of Slots tournament within 24 hours (00:00 – 23:59).
                        It can be filtered in a range between to dates. Max search date range: 7 days.</p>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xl-4 col-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Failed Deposits</h3>
                </div>
                <div class="card-body table-responsive">
                    <p>List of failed deposits made by customers within a time period, listing current day (00:00 – 23:59) by default.</p>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Bonus Abusers</h3>
                </div>
                <div class="card-body table-responsive">
                    <p>List of possible bonus abusers within a time period, listing current ant previous day (00:00 – 23:59) by default.
                    It can be filtered by wagered amount (1000 € by default) or bonus use percentage by a threshold.</p>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xl-4 col-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">AML monitoring</h3>
                </div>
                <div class="card-body table-responsive">
                    <p>Anti money laundering monitoring</p>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Similar account</h3>
                </div>
                <div class="card-body table-responsive">
                    <p>Similar account</p>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Min Fraud</h3>
                </div>
                <div class="card-body table-responsive">
                    <p>Min fraud</p>
                </div>
            </div>
        </div>
    </div>


@endsection
