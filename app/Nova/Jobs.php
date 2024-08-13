<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Text;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;


class Jobs extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Jobs>
     */
    public static $model = \App\Models\Jobs::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),

            Text::make('Title','titel')
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('Role','role')
                ->sortable()
                ->rules('required', 'max:255')
                ->maxlength(5)
                ,
                HasMany::make('Job Features', 'JobFeatures', JobFeatures::class ),
            HasMany::make('AppliedJob','ApllidJob','App\nova\AppliedJob'),

            BelongsTo::make('Users'),
            
            Text::make( 'Description', 'job_description')->hideFromIndex(),

            Text::make('Experience','experience')->hideFromIndex()
                ->sortable()
                ->rules('required', 'max:255'),
            Text::make('Job Location','job_location')->hideFromIndex()
                ->sortable()
                ->rules('required', 'max:255'),
            Text::make('Job Location Pin Code','job_location_pin_code')->hideFromIndex()
                ->sortable()
                ->rules('required', 'max:255'),
            Text::make('Company Name','company_name')->hideFromIndex()
                ->sortable()
                ->rules('required', 'max:255'),
            
            
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [];
    }
}
