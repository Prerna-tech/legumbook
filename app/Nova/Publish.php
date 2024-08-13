<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\HasMany;

class Publish extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\App\Models\Publish>
     */
    public static $model = \App\Models\Post::class;

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
    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query->whereNotNull('library_title');
    }
    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            Text::make('Library Title', 'library_title')
                ->sortable()
                ->rules('required', 'max:255'),
            Text::make('Library Link', 'library_link')
                ->sortable()
                ->rules('required', 'max:255'),
            Text::make('Description', 'post_description')->hideFromIndex()
                ->sortable()
                ->rules('required', 'max:255'),
            HasMany::make('Post Images', 'PostImage', PostImage::class ),
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
