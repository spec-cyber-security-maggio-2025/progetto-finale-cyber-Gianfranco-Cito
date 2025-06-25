<table class="table table-striped table-hover">
    <thead class="table-dark">
        <tr>
            <th scope="col">#</th>
            <th scope="col">Name</th>
            <th scope="col">Email</th>
            <th scope="col">Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($roleRequests as $user)
            <tr>
                <th scope="row">{{$user->id}}</th>
                <td>{{$user->name}}</td>
                <td>{{$user->email}}</td>



                <td>
    @switch($role)
        @case('admin')
            <form action="{{ route('admin.setAdmin', $user) }}" method="POST" class="d-inline">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-secondary">Enable {{$role}}</button>
            </form>
            @break

        @case('revisor')
            <form action="{{ route('admin.setRevisor', $user) }}" method="POST" class="d-inline">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-secondary">Enable {{$role}}</button>
            </form>
            @break

        @case('writer')
            <form action="{{ route('admin.setWriter', $user) }}" method="POST" class="d-inline">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-secondary">Enable {{$role}}</button>
            </form>
            @break
    @endswitch
</td>




            </tr>
        @endforeach
    </tbody>
</table>