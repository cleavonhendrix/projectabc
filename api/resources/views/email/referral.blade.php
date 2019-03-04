@component('email.header')
@endcomponent
<span class="text">
    <h3>ID Factory Invitation</h3>
    Hello {{$toEmail}}!
    <br>
    <br>
    Have a wonderful day!
    <br>
    You have been invited by {{$user->email}} to join the ID Factory.
    <br>
    {{$content}}
    <br>
    Click the button below to:
    <br>
    <a href="http://idfactory.ph/#/refer_register/{{$toEmail}}/{{$details->code}}">
        <button class="button">Accept & Register Now!</button>
    </a>
    <br>
</span>
@component('email.footer')
@endcomponent