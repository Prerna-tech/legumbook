<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Text;

class JobFeatures extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\JobFeatures>
     */
    public static $model = \App\Models\JobFeatures::class;

    public static function availableForNavigation(Request $request)
    {
        // Add your condition here to control resource visibility in the sidebar
        // For example, let's say you want to hide the resource based on some condition
        if ( $model = \App\Models\ApllidJob::class) {
            return false; // Hide the resource
        }

        return parent::availableForNavigation($request);
    }
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
            Text::make('Features','features')
            ->sortable()
            ->rules('required', 'max:255')
            ->maxlength(5),
            Text::make('Job ID','job_id')
            ->sortable()
            ->rules('required', 'max:255')
            ->maxlength(5)
            
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
