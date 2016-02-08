<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PostIt extends Model
{
    protected $fillable = ['identifier_id','message','coordinates','status'];
    protected $hidden = ['identifier_id'];
    protected $appends = ['vote_summary'];

    public function identifier(){
        return $this->belongsTo('App\Identifier');
    }

    public function votes(){
        return $this->hasMany('App\Vote','postit_id','id');
    }

    public function setCoordinatesAttribute(Array $coordinates){
        if (!empty($coordinates)){
            $this->attributes['coordinates'] = \DB::raw(sprintf('POINT(%s)', (is_array($coordinates)) ? implode(",",$coordinates) : $coordinates));
        }
    }

    public function getCoordinatesAttribute($value){
        if (is_array($value)){
            return $value;
        }
        $loc =  substr($value, 6);
        $loc = preg_replace('/[ ,]+/', ',', $loc, 1);
        list($lat, $lng) = array_pad(explode(",",substr($loc,0,-1)), 2, null);
        return ['lat'=>(float) $lat,'lon'=>(float) $lng];
    }

    public function scopeActive($query){
        return $query->where('status','>',0);
    }

    public function scopeHasVoted($query, $identity){
        return $query->leftJoin('votes', function ($join) use($identity) {

            return $join->on('votes.postit_id', '=', 'post_its.id')
                ->where('votes.identifier_id', '=', $identity->id);
        })->select(\DB::raw('post_its.*,votes.value as hasVoted, (select sum(votes.value) from votes where votes.postit_id = post_its.id) as totalVotes'))
        ->orderBy(\DB::raw('DATE(post_its.created_at)'),'desc')->orderBy('totalVotes','desc');
    }

    public function getVoteSummaryAttribute(){
        return \DB::table('votes')
            ->where('postit_id', $this->id)
            ->sum('value');
    }


}