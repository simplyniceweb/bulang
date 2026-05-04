<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'qr_code_key',
        'username',
        'password',
        'role',
        'status',
        'wallet',
        'session_id',
        'last_activity',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $dates = ['deleted_at'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // 'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Tickets sold by teller
    public function soldTickets()
    {
        return $this->hasMany(Ticket::class, 'teller_id');
    }

    // Tickets paid by teller
    public function paidTickets()
    {
        return $this->hasMany(Ticket::class, 'paid_by');
    }

    // Ledger entries
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function authorizedTransactions()
    {
        return $this->hasMany(Transaction::class, 'authorized_by');
    }
}
