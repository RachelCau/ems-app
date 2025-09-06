<?php

namespace App\Filament\Resources\InterviewScheduleResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Applicant;
use Filament\Tables\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Events\ApplicationStatusChanged;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Actions;

class ApplicantInterviewSchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'applicantInterviewSchedules';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('applicant_id')
                    ->label('Applicant')
                    ->options(Applicant::all()->pluck('full_name', 'id'))
                    ->required()
                    ->searchable(),
                Forms\Components\Select::make('status')
                    ->options([
                        'Scheduled' => 'Scheduled',
                        'Approved' => 'Approved',
                        'Declined' => 'Declined',
                    ])
                    ->default('Scheduled')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('applicant.applicant_number')
                    ->label('Applicant Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('applicant.full_name')
                    ->label('Applicant name')
                    ->searchable(['applicant.first_name', 'applicant.last_name'])
                    ->sortable(['applicant.first_name', 'applicant.last_name'])
                    ->wrap(),
                Tables\Columns\TextColumn::make('applicant.desired_program')
                    ->label('Program')
                    ->formatStateUsing(function ($state, $record) {
                        // First try to get from relationship
                        if ($state) {
                            return $state;
                        };
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => ucfirst(strtolower($state)))
                    ->color(fn($state) => match (strtolower($state)) {
                        'approved' => 'success',
                        'declined' => 'danger',
                        default => 'info',
                    }),
            ])
            ->filters([
                // Filters here
            ])
            ->headerActions([
                // Header actions here
            ])
            ->actions([
                // Approve button
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'Scheduled')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $applicant = $record->applicant;
                        $oldStatus = $applicant->status;
                        
                        // Update statuses
                        $record->status = 'Approved';
                        $record->save();
                        $applicant->status = 'for enrollment';
                        $applicant->save();
                        
                        // Email notification data
                        $interviewDate = $record->interviewSchedule && $record->interviewSchedule->date 
                            ? $record->interviewSchedule->date->format('F j, Y') 
                            : 'Not specified';
                        
                        $emailData = [
                            'program' => $applicant->desired_program,
                            'interview_date' => $interviewDate,
                            'next_steps' => 'Please proceed to our Registrar\'s Office to complete your enrollment process.',
                            'applicant_name' => $applicant->full_name,
                        ];
                        
                        event(new ApplicationStatusChanged(
                            $applicant, 
                            $oldStatus, 
                            'for enrollment',
                            $emailData
                        ));
                        
                        Notification::make()
                            ->success()
                            ->title('Applicant Approved')
                            ->body("The applicant has been approved for enrollment.")
                            ->send();
                    }),
                
                // Decline button
                Tables\Actions\Action::make('decline')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'Scheduled')
                    ->form([
                        Textarea::make('reason')
                            ->label('Reason for Declining')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function ($data, $record) {
                        $applicant = $record->applicant;
                        $oldStatus = $applicant->status;
                        
                        // Update statuses
                        $record->status = 'Declined';
                        $record->save();
                        $applicant->status = 'declined';
                        $applicant->save();
                        
                        // Email notification data
                        $interviewDate = $record->interviewSchedule && $record->interviewSchedule->date 
                            ? $record->interviewSchedule->date->format('F j, Y') 
                            : 'Not specified';
                        
                        $reasonData = [
                            'reason' => $data['reason'],
                            'interview_date' => $interviewDate,
                            'program' => $applicant->desired_program,
                            'applicant_name' => $applicant->full_name,
                        ];
                        
                        event(new ApplicationStatusChanged(
                            $applicant, 
                            $oldStatus, 
                            'declined',
                            $reasonData
                        ));
                        
                        Notification::make()
                            ->success()
                            ->title('Applicant Declined')
                            ->body("The applicant has been declined.")
                            ->send();
                    }),
                
                // View/Edit/Delete actions
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // No bulk actions needed
            ]);
    }
}
