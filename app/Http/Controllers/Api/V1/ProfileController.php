<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Auth\UpdateProfileRequest;
use App\Http\Resources\Api\V1\ProfileResource;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return $this->success(new ProfileResource($request->user()));
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();

        if ($request->has('firstName') || $request->has('surname')) {
            $firstName = $request->input('firstName', $user->first_name ?? explode(' ', $user->name)[0] ?? '');
            $surname = $request->input('surname', $user->last_name ?? '');
            $user->name = trim("{$firstName} {$surname}");
            $user->first_name = $firstName;
            $user->last_name = $surname;
        }

        $user->fill($request->only(['email', 'phone', 'address', 'gender']));

        if ($request->has('dateOfBirth')) {
            $user->date_of_birth = $request->dateOfBirth;
        }

        $user->save();

        return $this->success(
            new ProfileResource($user->fresh()),
            'Profile updated successfully'
        );
    }
}
