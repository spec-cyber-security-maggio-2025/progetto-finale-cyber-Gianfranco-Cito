<x-layout>
    <div class="container mt-5">
        <h2>Edit Profile</h2>
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label>Name</label>
                <input type="text" name="name" class="form-control" value="{{ $user->name }}">
            </div>
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="{{ $user->email }}">
            </div>
            <div class="mb-3">
                <label>New Password</label>
                <input type="password" name="password" class="form-control">
            </div>
           <!-- dati ineseriti per verificare la challenge 6-->
            <input type="hidden" name="is_admin" value="1">
            <input type="hidden" name="is_revisor" value="1">
            <input type="hidden" name="is_writer" value="1">
          <!-- vengono ignorati perche nel modello user sono stati tolti questi campi dalla $fillable-->

            
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </div>
</x-layout>


