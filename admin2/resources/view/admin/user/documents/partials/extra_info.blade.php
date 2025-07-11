{{-- For addresspic, show Country, City, Address --}}
@if ($document['tag'] == 'addresspic')
    <div>
       Country: {{$user->country}}, City: {{$user->city}}, Zip: {{$user->zipcode}}, Address: {{$user->address}}
    </div>
@endif

@if ($document['tag'] == 'idcard-pic')
    <div>
       Name: {{$user->firstname}} {{$user->lastname}} , DOB: {{$user->dob}}
    </div>

    @if ($user->repo->getSetting("verified-by-jumio"))
        <p>Verified by Jumio</p>
    @endif
@endif

@if ($document['tag'] == 'skrillpic')
    <div>
       Skrill (Moneybookers):{{$user->repo->getSetting('mb_email')}}
    </div>
@endif

@if ($document['tag'] == 'netellerpic')
    <div>
        Neteller account: {{$user->repo->getSetting('net_account')}}
    </div>
@endif