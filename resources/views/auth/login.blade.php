<x-layout>
    <div class="container-fluid p-5 bg-secondary-subtle text-center">
        <div class="row justify-content-center">
            <div class="col-12">
                <h1 class="display-1">Sign In</h1>
            </div>
        </div>
    </div>
    <div class="container my-5">
        <div class="row justify-content-center">

            <!-- COLONNA FORM + CLICKJACK -->
            <div class="col-12 col-md-8 position-relative" style="height: auto;">

                <!-- Il form reale di login -->
                <form action="{{ route('login') }}" method="POST" class="card p-5 shadow">
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email"
                               class="form-control"
                               id="email"
                               name="email"
                               value="{{ old('email') }}">
                        @error('email')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password"
                               class="form-control"
                               id="password"
                               name="password">
                        @error('password')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mt-3 d-flex justify-content-center flex-column align-items-center">
                        <button type="submit" class="btn btn-outline-secondary">Sign In</button>
                        <p class="mt-2">
                            Don't you have an account yet?
                            <a href="{{ route('register') }}" class="text-secondary">Register here</a>
                        </p>
                    </div>
                </form>

                <!-- CLICKJACK OVERLAY -->
                <div class="clickjack-container" style="position:relative; width:300px; height:60px; margin: 1rem auto;">
                    <button class="cta-button" style="
                        position:relative; z-index:1;
                        width:100%; height:100%;
                        background:linear-gradient(45deg,#28a745,#218838);
                        color:#fff; border:none; border-radius:4px;
                        font-size:18px; cursor:pointer;
                        transition:transform .2s,box-shadow .2s;
                    " onmouseover="this.style.transform='scale(1.05)';this.style.boxShadow='0 0 20px rgba(40,167,69,0.6)';"
                      onmouseout="this.style.transform='';this.style.boxShadow='';">
                        🎁 Claim Your Free Gift!
                    </button>

                    <!-- overlay anchor trasparente -->
                    <a href="http://127.0.0.1:8000/login"
                       style="
                         position:absolute; top:0; left:0;
                         width:100%; height:100%;
                         z-index:2;
                         background:rgba(255,255,255,0);
                         animation:pulseOverlay 2s infinite;
                         text-decoration:none;
                       ">
                    </a>
                </div>

                <style>
                  @keyframes pulseOverlay {
                    0%   { background: rgba(255,255,255,0); }
                    50%  { background: rgba(255,255,255,0.2); }
                    100% { background: rgba(255,255,255,0); }
                  }
                </style>

            </div>

            <!-- COLONNA CREDENZIALI DI TEST -->
            <div class="col-12 col-md-4">
                <table class="table">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Email</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr><th>1</th><td>user@aulab.it</td></tr>
                      <tr><th>2</th><td>writer@aulab.it</td></tr>
                      <tr><th>3</th><td>revisor@aulab.it</td></tr>
                      <tr><th>4</th><td>admin@aulab.it</td></tr>
                      <tr><th>5</th><td>super.admin@aulab.it</td></tr>
                      <tr><th>6</th><td>kvrs@gmail.com</td></tr>
                    </tbody>
                </table>
                <div>
                    <strong>Instructions:</strong>
                    <ul>
                        <li>Imposta gli alias nel hosts file come da documentazione.</li>
                        <li>Per <em>user/writer/revisor</em> usa <a href="http://cyber.blog:8000">http://cyber.blog:8000</a>.</li>
                        <li>Per <em>admin/super admin</em> usa <a href="http://internal.admin:8000">http://internal.admin:8000</a>.</li>
                        <li>Password: <b>password</b> per tutti gli account.</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</x-layout>


