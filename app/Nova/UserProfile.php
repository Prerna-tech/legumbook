<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Text;

class UserProfile extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\UserProfile>
     */
    public static $model = \App\Models\UserProfile::class;

    public static function availableForNavigation(Request $request)
    {
        // Add your condition here to control resource visibility in the sidebar
        // For example, let's say you want to hide the resource based on some condition
        if ( $model = \App\Models\UserProfile::class) {
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
            Text::make('Designation','designation')
                ->sortable()
                ->rules('required', 'max:255'),
            Text::make('Purpose to use app','purpose_to_use_app')->hideFromIndex()
                ->sortable()
                ->rules('required', 'max:255'),
            Text::make('DOB','dob')
                ->sortable()
                ->rules('required', 'max:255'),
            Text::make('Address','addres1')->hideFromIndex()
                ->sortable()
                ->rules('required', 'max:255'),
            Text::make('Address2','addres2')->hideFromIndex()
                ->sortable()
                ->rules('required', 'max:255'),
            Text::make('City','city')
                ->sortable()
                ->rules('required', 'max:255'),
            Text::make('Satae','state')
                ->sortable()
                ->rules('required', 'max:255'),
            Text::make('Zip','zip')
                ->sortable()
                ->rules('required', 'max:255'),
            Text::make('Country','country')
                ->sortable()
                ->rules('required', 'max:255'),
            Text::make('Bio','bio')->hideFromIndex()
                ->sortable()
                ->rules('required', 'max:255')
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
